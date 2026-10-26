<?php

declare(strict_types=1);

namespace App\Service;

use Bitrix24\SDK\Core\Exceptions\BaseException;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Сервис для работы со смарт-процессами Битрикс24:
 *  - «Дизайнеры» (entityTypeId: 1042)
 *  - «Уведомление» (entityTypeId: 1044)
 */
class Bitrix24DesignerService
{
    private ServiceBuilder $serviceBuilder;

    public function __construct(
        private string $webhookUrl,
        private int $designerEntityTypeId,
        private int $notificationEntityTypeId,
        private LoggerInterface $logger
    ) {
        $this->serviceBuilder = (new ServiceBuilderFactory(new EventDispatcher(), $logger))
            ->initFromWebhook($webhookUrl);
    }

    /**
     * Создаёт элемент в смарт-процессе «Дизайнеры».
     *
     * @param array $designerData Данные дизайнера (см. маппинг ниже).
     * @return int|null ID созданного элемента или null при ошибке.
     */
    public function createDesignerItem(array $designerData): ?int
    {
        try {
            $fields = [
                'title'        => $this->buildTitle($designerData),
                'assignedById' => $designerData['assignedById'] ?? null,
                'sourceId'     => $designerData['sourceId'] ?? null,
                'contactIds'   => $designerData['contactIds'] ?? null,
                'companyId'    => $designerData['companyId'] ?? null,
                'opened'       => $designerData['opened'] ?? 'Y',
            ];

            // Пользовательские поля «Дизайнеров»
            $fullName = $designerData['fullName'] ?? $this->buildTitle($designerData);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681584', $fullName);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681594', $designerData['nrec'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681605', $designerData['firstContactDate'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681675', $designerData['phone'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681681', $designerData['email'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681689', $designerData['telegram'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681701', $designerData['whatsapp'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1785681727', $designerData['agentStatus'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1786093641033', $designerData['additionalContacts'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1786521823', $designerData['sourceCustom'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1786523515', $designerData['edo'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm8_1786523572552', $designerData['city'] ?? null);

            $this->assignIfNotEmpty($fields, 'ufCrm8_1791187413813', true);

            // Связь с уведомлением, если уже известно
            $this->assignIfNotEmpty($fields, 'parentId1044', $designerData['parentId1044'] ?? null);

            $fields = array_filter($fields, static fn($v) => $v !== null);

            $result = $this->serviceBuilder
                ->getCRMScope()
                ->item()
                ->add($this->designerEntityTypeId, $fields);

            $itemId = (int)$result->item()->id;

            $this->logger->info('Bitrix24: создан элемент «Дизайнеры».', [
                'item_id'        => $itemId,
                'entity_type_id' => $this->designerEntityTypeId,
            ]);

            return $itemId;

        } catch (BaseException $e) {
            $this->logger->error('Bitrix24: ошибка создания «Дизайнеры».', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'data'    => $designerData,
            ]);

            return null;
        }
    }

    /**
     * Создаёт элемент в смарт-процессе «Уведомление».
     *
     * @param array $notificationData Данные уведомления.
     * @return int|null ID созданного элемента или null при ошибке.
     */
    public function createNotificationItem(array $notificationData): ?int
    {
        try {
            $fields = [
                'title'        => $notificationData['title'] ?? 'Новое уведомление',
                'assignedById' => $notificationData['assignedById'] ?? null,
                'contactIds'   => $notificationData['contactIds'] ?? null,
                'companyId'    => $notificationData['companyId'] ?? null,
                'opened'       => $notificationData['opened'] ?? 'Y',
                // sourceId НЕ передаём: у «Уведомления» source выключен
            ];

            // Пользовательские поля «Уведомления»
            $this->assignIfNotEmpty($fields, 'ufCrm10_1785741627984', $notificationData['designerPhone'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm10_1785741642648', $notificationData['clientPhone'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm10_1785741651080', $notificationData['deliveryAddress'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm10_1785741661079', $notificationData['clientFullName'] ?? null);
            $this->assignIfNotEmpty($fields, 'ufCrm10_1785741723360', $notificationData['project'] ?? null);

            // Связь с дизайнером — ключевое поле
            $this->assignIfNotEmpty($fields, 'parentId1042', $notificationData['parentId1042'] ?? null);
            if (!empty($notificationData['projectFiles'])) {
                $fields['ufCrm10_1785741729607'] = $notificationData['projectFiles'];
            }

            $fields = array_filter($fields, static fn($v) => $v !== null);

            $this->logger->debug('Notification fields', $fields);

            $result = $this->serviceBuilder
                ->getCRMScope()
                ->item()
                ->add($this->notificationEntityTypeId, $fields);

            $itemId = (int)$result->item()->id;

            $this->logger->info('Bitrix24: создан элемент «Уведомление».', [
                'item_id'        => $itemId,
                'entity_type_id' => $this->notificationEntityTypeId,
            ]);

            return $itemId;

        } catch (BaseException $e) {
            $this->logger->error('Bitrix24: ошибка создания «Уведомление».', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'data'    => $notificationData,
            ]);

            return null;
        }
    }

    /**
     * Создаёт уведомление с файлами, переданными в Base64.
     *
     * @param array $notificationData
     * @param string[] $localFilePaths Абсолютные пути к файлам на сервере.
     * @return int|null
     */
    public function createNotificationItemWithFiles(array $notificationData, array $localFilePaths = []): ?int
    {
        if ($localFilePaths === []) {
            return $this->createNotificationItem($notificationData);
        }

        $filePath = $localFilePaths[0]; // поле одиночное — берём только первый файл

        if (!is_file($filePath)) {
            $this->logger->warning('Bitrix24: файл не найден, пропускаем.', ['path' => $filePath]);
            return $this->createNotificationItem($notificationData);
        }

        $content = file_get_contents($filePath);
        if ($content === false || $content === '') {
            $this->logger->warning('Bitrix24: не удалось прочитать файл.', ['path' => $filePath]);
            return $this->createNotificationItem($notificationData);
        }

        // Поле ufCrm10_1785741729607 — одиночное, формат: ["имя", "base64"]
        $notificationData['projectFiles'] = [
            basename($filePath),
            base64_encode($content),
        ];

        $this->logger->info('Bitrix24: файл подготовлен к отправке.', [
            'name'       => basename($filePath),
            'size'       => strlen($content),
            'base64_len' => strlen(base64_encode($content)),
        ]);

        return $this->createNotificationItem($notificationData);
    }

    /**
     * Создаёт дизайнера и сразу связанное уведомление.
     *
     * @return array{designerId: ?int, notificationId: ?int}
     */
    public function createDesignerWithNotification(array $designerData, ?array $notificationData = null): array
    {
        $designerId = $this->createDesignerItem($designerData);

        if ($designerId === null) {
            return ['designerId' => null, 'notificationId' => null];
        }

        // Формируем уведомление, автоматически проставляя связь с дизайнером
        $notificationData ??= [];
        $notificationData['parentId1042'] ??= $designerId;
        $notificationData['title'] ??= 'Новый дизайнер: ' . $this->buildTitle($designerData);
        $notificationData['assignedById'] ??= $designerData['assignedById'] ?? null;
        $notificationData['designerPhone'] ??= $designerData['phone'] ?? null;

        $notificationId = $this->createNotificationItem($notificationData);

        return [
            'designerId'     => $designerId,
            'notificationId' => $notificationId,
        ];
    }

    /**
     * Заголовок элемента.
     */
    private function buildTitle(array $data): string
    {
        if (!empty($data['fullName'])) {
            return trim($data['fullName']);
        }

        $parts = array_filter([
            $data['lastName']   ?? null,
            $data['name']       ?? null,
            $data['middleName'] ?? null,
        ]);

        $title = trim(implode(' ', $parts));

        return $title !== '' ? $title : 'Новый дизайнер';
    }

    /**
     * Ищет дизайнера в Битрикс24 по номеру телефона.
     *
     * @param string $phone Номер телефона.
     * @return int|null ID найденного дизайнера или null, если не найден.
     */
    public function findDesignerByPhone(string $phone): ?int
    {
        try {
            $result = $this->serviceBuilder
                ->getCRMScope()
                ->item()
                ->list(
                    $this->designerEntityTypeId,
                    [],                                       // order
                    ['ufCrm8_1785681675' => $phone],          // filter
                    ['id']                                    // select
                );

            $items = $result->getItems();

            return $items !== [] ? (int)$items[0]->id : null;

        } catch (BaseException $e) {
            $this->logger->error('Bitrix24: ошибка поиска дизайнера по телефону.', [
                'message' => $e->getMessage(),
                'phone'   => $phone,
            ]);

            return null;
        }
    }

    /**
     * Добавляет поле, только если значение не пустое.
     */
    private function assignIfNotEmpty(array &$fields, string $code, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $fields[$code] = $value;
    }
}