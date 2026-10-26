<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Bitrix24DesignerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:bitrix24:test',
    description: 'Тестовое создание дизайнера и уведомления в Битрикс24',
)]
class Bitrix24TestCommand extends Command
{
    public function __construct(
        private readonly Bitrix24DesignerService $bitrix24Service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Только показать данные, не отправлять в Битрикс24')
            ->addOption('only-designer', null, InputOption::VALUE_NONE, 'Создать только дизайнера, без уведомления')
            ->addOption('only-notification', null, InputOption::VALUE_NONE, 'Создать только уведомление (без дизайнера)')
            ->addOption('designer-id', null, InputOption::VALUE_REQUIRED, 'ID существующего дизайнера для привязки уведомления')
            ->addOption('find-designer', null, InputOption::VALUE_REQUIRED, 'Найти дизайнера по телефону');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Тест интеграции с Битрикс24');

        // Тестовые данные дизайнера (замените на реальные при необходимости)
        $designerData = [
            'name'         => 'Иван2',
            'lastName'     => 'Тестов2',
            'middleName'   => 'Тестович2',
            'phone'        => '+7 (999) 123-45-68',
            'email'        => 'test.designer+' . time() . '@example.com',
            'telegram'     => '@test_designer',
            'whatsapp'     => '+79991234567',
            'city'         => 'Москва',
            'nrec'         => 'NREC-TEST-' . time(),
            'assignedById' => 16,          // ID ответственного (замените на реальный)
            'sourceId'     => 'WEB',   // код источника (проверьте в CRM)
            'opened'       => 'Y',
            'sourceCustom' => 'Форма регистрации lkd.askona.ru',
        ];

        $notificationData = [
            'title'            => 'Тестовое уведомление ' . date('Y-m-d H:i:s'),
            'designerPhone'    => '+7 (999) 123-45-67',
            'clientFullName'   => 'Пётр Клиентов',
            'clientPhone'      => '+7 (999) 765-43-21',
            'deliveryAddress'  => 'г. Москва, ул. Тестовая, д. 1',
            'project'          => 'Тестовый проект #' . time(),
            'assignedById'     => 16,
            'opened'           => 'Y',
        ];

        // Показать, что собираемся отправить
        $io->section('Данные дизайнера');
        $io->table(
            ['Поле', 'Значение'],
            array_map(
                static fn($k, $v) => [$k, is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v],
                array_keys($designerData),
                array_values($designerData)
            )
        );

        $io->section('Данные уведомления');
        $io->table(
            ['Поле', 'Значение'],
            array_map(
                static fn($k, $v) => [$k, is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v],
                array_keys($notificationData),
                array_values($notificationData)
            )
        );

        if ($phone = $input->getOption('find-designer')) {
            $id = $this->bitrix24Service->findDesignerByPhone($phone);
            if ($id === null) {
                $io->warning('Дизайнер с таким телефоном не найден.');
            } else {
                $io->success(sprintf('Найден дизайнер. ID: %d', $id));
            }
            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            $io->warning('Режим --dry-run: данные в Битрикс24 не отправлены.');
            return Command::SUCCESS;
        }

        if (!$io->confirm('Отправить данные в Битрикс24?', false)) {
            $io->note('Отменено пользователем.');
            return Command::SUCCESS;
        }

        // --- Сценарий 1: только уведомление ---
        if ($input->getOption('only-notification')) {
            $designerId = $input->getOption('designer-id');
            if ($designerId === null) {
                $io->error('Для --only-notification нужен --designer-id=<ID>.');
                return Command::FAILURE;
            }

            $notificationData['parentId1042'] = (int) $designerId;

            $io->section('Создание уведомления');
            $notificationId = $this->bitrix24Service->createNotificationItem($notificationData);

            if ($notificationId === null) {
                $io->error('Не удалось создать уведомление. Смотрите логи.');
                return Command::FAILURE;
            }

            $io->success(sprintf('Уведомление создано. ID: %d', $notificationId));
            return Command::SUCCESS;
        }

        // --- Сценарий 2: только дизайнер ---
        if ($input->getOption('only-designer')) {
            $io->section('Создание дизайнера');
            $designerId = $this->bitrix24Service->createDesignerItem($designerData);

            if ($designerId === null) {
                $io->error('Не удалось создать дизайнера. Смотрите логи.');
                return Command::FAILURE;
            }

            $io->success(sprintf('Дизайнер создан. ID: %d', $designerId));
            $io->note('Откройте CRM → Дизайнеры, чтобы проверить запись.');
            return Command::SUCCESS;
        }

        // --- Сценарий 3 (по умолчанию): дизайнер + уведомление ---
        $io->section('Создание дизайнера и связанного уведомления');

        $result = $this->bitrix24Service->createDesignerWithNotification(
            $designerData,
            $notificationData
        );

        if ($result['designerId'] === null) {
            $io->error('Не удалось создать дизайнера. Смотрите логи.');
            return Command::FAILURE;
        }

        $io->success(sprintf('Дизайнер создан. ID: %d', $result['designerId']));

        if ($result['notificationId'] === null) {
            $io->warning('Дизайнер создан, но уведомление не удалось создать.');
            return Command::FAILURE;
        }

        $io->success(sprintf('Уведомление создано. ID: %d', $result['notificationId']));
        $io->note([
            'Откройте CRM → Дизайнеры — увидите новую запись.',
            'Откройте CRM → Уведомление — увидите связанное уведомление.',
            'Проверьте, что в карточке дизайнера в поле «Уведомление» подтянулась связь.',
        ]);

        return Command::SUCCESS;
    }
}