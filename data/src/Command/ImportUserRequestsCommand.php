<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\UserRequest;
use App\Service\PhoneParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import:user-requests', description: 'Разовый импорт user_request из XML')]
class ImportUserRequestsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Путь к XML-файлу');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        if (!is_file($file)) {
            $output->writeln("<error>Файл не найден: $file</error>");
            return Command::FAILURE;
        }

        $raw = file_get_contents($file);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1251');
        }
        $raw = preg_replace('/<\?xml[^?]*\?>/i', '', $raw);
        $raw = trim($raw);

        // Оборачиваем только если корневого тега нет вообще
        // (файл — просто последовательность <DATA_RECORD>)
        if (!preg_match('/^<[a-zA-Z_]/', $raw)) {
            $raw = "<root>\n" . $raw . "\n</root>";
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw);
        if ($xml === false) {
            foreach (libxml_get_errors() as $err) {
                $output->writeln('<error>' . trim($err->message) . '</error>');
            }
            return Command::FAILURE;
        }

        $userRepo = $this->em->getRepository(User::class);
        $phoneMap = $this->buildPhoneMap($userRepo, $output);

        $inserted = 0;
        $skipped  = 0;
        $notFound = [];

        $this->em->beginTransaction();

        try {
            $records = $xml->xpath('//DATA_RECORD');
            if ($records === false) {
                $output->writeln('<error>xpath вернул false</error>');
                return Command::FAILURE;
            }
            $output->writeln(sprintf('Найдено записей: %d', count($records)));

            foreach ($records as $record) {
                $d = $this->recordToArray($record);

                // --- Дизайнер: телефон (E.164) -> email ---
                $user = null;
                $designerPhoneRaw  = $d['designer_phone'];
                $designerPhoneNorm = PhoneParser::normalize($designerPhoneRaw);

                if (PhoneParser::isValid($designerPhoneNorm) && isset($phoneMap[$designerPhoneNorm])) {
                    $user = $phoneMap[$designerPhoneNorm];
                }

                if ($user === null && $d['designer_email'] !== '') {
                    $emailNorm = mb_strtolower(trim($d['designer_email']));
                    $user = $userRepo->findOneBy(['email' => $emailNorm]);
                }

                if ($user === null) {
                    $notFound[] = sprintf(
                        'phone=%s (norm=%s), email=%s, request_name=%s',
                        $designerPhoneRaw,
                        $designerPhoneNorm,
                        $d['designer_email'],
                        $d['request_name']
                    );
                    $skipped++;
                    continue;
                }

                // --- Телефон клиента: нормализуем, но не пропускаем заявку при ошибке ---
                $clientPhoneRaw  = $d['phone'];
                $clientPhoneNorm = PhoneParser::normalize($clientPhoneRaw);
                $clientPhoneOut  = PhoneParser::isValid($clientPhoneNorm)
                    ? $clientPhoneNorm
                    : $clientPhoneRaw; // оставляем как есть, если невалиден

                $request = new UserRequest();
                $request->setUser($user);
                $request->setData([
                    'type'          => $d['type'],
                    'request_name'  => $d['request_name'],
                    'phone'         => $clientPhoneOut,
                    'address'       => $d['address'],
                    'product_list'  => $d['product_list'],
                    'link'          => $d['link'],
                    'way'           => $d['way'],
                    'question'      => $d['question'],
                    'invoice_files' => $d['invoice_files_arr'],
                    'project_files' => $d['project_files_arr'],
                ]);
                $request->setCreatedAt(new \DateTimeImmutable());
                $request->setProcessed(false);

                $this->em->persist($request);
                $inserted++;
            }

            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            $output->writeln('<error>Ошибка импорта: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln("Вставлено: $inserted, пропущено (дизайнер не найден): $skipped");
        foreach ($notFound as $line) {
            $output->writeln('  - ' . $line);
        }

        return Command::SUCCESS;
    }

    private function recordToArray(\SimpleXMLElement $record): array
    {
        $phones = [];
        foreach ($record->phone as $p) {
            $phones[] = (string)$p;
        }

        return [
            'type'               => (string)$record->type,
            'designer_phone'     => $phones[0] ?? '',
            'designer_email'     => (string)$record->email,
            'request_name'       => (string)$record->request_name,
            'phone'              => $phones[1] ?? ($phones[0] ?? ''),
            'address'            => (string)$record->address,
            'product_list'       => (string)$record->product_list,
            'link'               => (string)$record->link,
            'way'                => (string)$record->way,
            'question'           => (string)$record->question,
            'invoice_files_arr'  => $this->decodeFiles((string)$record->invoice_files),
            'project_files_arr'  => $this->decodeFiles((string)$record->project_files),
        ];
    }

    private function decodeFiles(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Map: E.164 => User, построенный из телефонов в БД.
     * Битые/пустые телефоны пропускаем, но предупреждаем.
     */
    private function buildPhoneMap($userRepo, $output): array
    {
        $map = [];
        $bad = 0;

        foreach ($userRepo->findAll() as $user) {
            $phoneRaw  = (string)$user->getPhone(); // или ->getProfile()->getPhone()
            if ($phoneRaw === '') {
                continue;
            }

            $phoneNorm = PhoneParser::normalize($phoneRaw);
            if (!PhoneParser::isValid($phoneNorm)) {
                $bad++;
                continue;
            }

            $map[$phoneNorm] = $user;
        }

        if ($bad > 0) {
            $output->writeln("<comment>Пользователей с невалидным телефоном (не попадут в map): $bad</comment>");
        }

        $output->writeln(sprintf('В map загружено телефонов: %d', count($map)));

        return $map;
    }
}