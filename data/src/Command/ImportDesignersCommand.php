<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PhoneParser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:designers',
    description: 'Импорт дизайнеров из XLSX.',
)]
class ImportDesignersCommand extends Command
{
    private const FLUSH_EVERY = 50;

    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly ManagerRegistry $doctrine,
    ) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Путь к XLSX-файлу.');
    }

    /**
     * Очищает строку от невидимых символов (неразрывные пробелы, BOM, zero-width).
     */
    private function cleanString(string $value): string
    {
        // \u{00A0} — неразрывный пробел, \u{200B} — zero-width space, \u{FEFF} — BOM
        $value = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{200E}\x{200F}]/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value); // схлопываем пробелы
        return trim($value);
    }

    /**
     * Разбирает строку вида "ИП Иванов Иван" или "ООО Капсула"
     * на legalEntity и fullName.
     * Если форма не найдена — по умолчанию "Самозанятый".
     *
     * @return array{legalEntity: string, fullName: ?string}
     */
    private function parseLegalEntityAndName(string $raw): array
    {
        $raw = $this->cleanString($raw);
        if ($raw === '') {
            return ['legalEntity' => 'Самозанятый', 'fullName' => null];
        }

        $forms = ['Самозанятый', 'Самозанятая', 'ИП', 'ООО', 'АО', 'ПАО', 'ЗАО'];

        foreach ($forms as $form) {
            $pattern = '/^' . preg_quote($form, '/') . '\s+(.+)$/iu';
            if (preg_match($pattern, $raw, $m)) {
                $name = $this->cleanString($m[1]);
                return [
                    'legalEntity' => $form,
                    'fullName'    => $name !== '' ? $name : null,
                ];
            }
        }

        return ['legalEntity' => 'Самозанятый', 'fullName' => $raw];
    }

    private function resetEntityManagerIfClosed(): void
    {
        if ($this->em->isOpen()) {
            $this->em->clear();
            return;
        }

        $this->doctrine->resetManager();
        $this->em = $this->doctrine->getManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string)$input->getArgument('file');

        if (!is_file($path)) {
            $io->error("Файл не найден: $path");
            return Command::FAILURE;
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        $created = 0;
        $skipped = 0;
        $errors  = 0;

        $seenPhones = [];
        $seenEmails = [];
        $seenNames  = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue; // заголовки
            }

            if (!$sheet->getRowDimension($i + 1)->getVisible()) {
                continue;
            }

            $phone   = $this->cleanString((string)($row[12] ?? ''));
            $email   = $this->cleanString((string)($row[13] ?? ''));
            $city    = $this->cleanString((string)($row[11] ?? ''));
            $site    = $this->cleanString((string)($row[14] ?? ''));
            $rawName = $this->cleanString((string)($row[8] ?? ''));

            // Пропускаем строки, где нет ни телефона, ни ФИО
            if ($phone === '' && $rawName === '') {
                continue;
            }

            $parsed      = $this->parseLegalEntityAndName($rawName);
            $legalEntity = $parsed['legalEntity'];
            $fullName    = $parsed['fullName'];

            try {
                // --- Телефон: мягкая нормализация ---
                $normalizedPhone = null;
                if ($phone !== '') {
                    $normalizedPhone = PhoneParser::normalize($phone);
                    if (!PhoneParser::isValid($normalizedPhone)) {
                        $io->warning("Некорректный телефон, пропуск: " . ($fullName ?? '—') . " / $phone");
                        $skipped++;
                        continue;
                    }

                    if (isset($seenPhones[$normalizedPhone])) {
                        $io->writeln("Пропуск (дубликат телефона в файле): $fullName / $normalizedPhone");
                        $skipped++;
                        continue;
                    }

                    if ($this->userRepository->findOneBy(['phone' => $normalizedPhone])) {
                        $io->writeln("Пропуск (телефон уже есть в БД): $fullName / $normalizedPhone");
                        $skipped++;
                        continue;
                    }
                }

                // --- Email: чистим и валидируем ДО setEmail ---
                $emailLower = null;
                if ($email !== '') {
                    $emailLower = strtolower($email);

                    if ($emailLower === '' || filter_var($emailLower, FILTER_VALIDATE_EMAIL) === false) {
                        $io->warning("Некорректный email, сохраняем без email: " . ($fullName ?? '—') . " / [$email]");
                        $emailLower = null; // не пропускаем строку, просто не сохраняем email
                    }
                }

                if ($emailLower !== null) {
                    if (isset($seenEmails[$emailLower])) {
                        $io->writeln("Пропуск (дубликат email в файле): $fullName / $emailLower");
                        $skipped++;
                        continue;
                    }

                    if ($this->userRepository->findOneBy(['email' => $emailLower])) {
                        $io->writeln("Пропуск (email уже есть в БД): $fullName / $emailLower");
                        $skipped++;
                        continue;
                    }
                }

                // --- ФИО ---
                if ($fullName !== null) {
                    if (isset($seenNames[$fullName])) {
                        $io->writeln("Пропуск (дубликат ФИО в файле): $fullName");
                        $skipped++;
                        continue;
                    }

                    $existingByFullName = $this->em->createQueryBuilder()
                        ->select('u')
                        ->from(User::class, 'u')
                        ->join('u.profile', 'p')
                        ->where('p.fullName = :fullName')
                        ->setParameter('fullName', $fullName)
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
                    if ($existingByFullName) {
                        $io->writeln("Пропуск (ФИО уже есть в БД): $fullName");
                        $skipped++;
                        continue;
                    }
                }

                // --- Создание ---
                $user = new User();
                $user->setRoles([User::ROLE_DESIGNER]);
                $user->setStatus(User::STATUS_ACTIVE);
                $user->setPassword(null);

                if ($normalizedPhone !== null) {
                    $user->setPhone($normalizedPhone);
                }
                if ($emailLower !== null) {
                    $user->setEmail($emailLower);
                }

                $profile = $user->getProfile();
                if ($fullName !== null) {
                    $profile->setFullName($fullName);
                }
                $profile->setLegalEntity($legalEntity);
                if ($city !== '') {
                    $profile->setCity($city);
                }
                if ($site !== '') {
                    $profile->setSites([$site]);
                }
                $profile->setProfession('Дизайнер');

                $requisites = $user->getRequisites();
                if ($normalizedPhone !== null) {
                    $requisites->setPhone($normalizedPhone);
                }
                if ($emailLower !== null) {
                    $requisites->setEmail($emailLower);
                }
                if ($fullName !== null) {
                    $requisites->setFullName($fullName);
                }
                $requisites->setLegalEntity($legalEntity);

                $this->em->persist($user);
                $created++;

                if ($normalizedPhone !== null) {
                    $seenPhones[$normalizedPhone] = true;
                }
                if ($emailLower !== null) {
                    $seenEmails[$emailLower] = true;
                }
                if ($fullName !== null) {
                    $seenNames[$fullName] = true;
                }

                if ($created % self::FLUSH_EVERY === 0) {
                    $this->em->flush();
                    $this->em->clear();
                    $io->writeln("--- flush after $created ---");
                }

                $io->writeln("Создан: " . ($fullName ?? '—') . " / " . ($normalizedPhone ?? '—') . " / " . ($emailLower ?: '—'));
            } catch (\Throwable $e) {
                $io->error("Ошибка в строке $i: " . $e->getMessage());
                $errors++;

                $this->resetEntityManagerIfClosed();

                if (!$this->em->isOpen()) {
                    $io->error('EntityManager не удалось восстановить. Прерываем импорт.');
                    return Command::FAILURE;
                }
            }
        }

        try {
            if ($this->em->isOpen()) {
                $this->em->flush();
            }
        } catch (\Throwable $e) {
            $io->error('Финальный flush не удался: ' . $e->getMessage());
            $errors++;
        }

        $io->success("Создано: $created, пропущено: $skipped, ошибок: $errors");

        return Command::SUCCESS;
    }
}