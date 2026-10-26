<?php

namespace App\Service;

use App\Entity\Requisites;
use InvalidArgumentException;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\MimeTypes;

use Symfony\Contracts\Translation\TranslatorInterface;
//use function Symfony\Component\Translation\t;

class ContractGeneratorService
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly MorphologyService $morphology,
        private TranslatorInterface $translator,
        public array $templates = [
            Requisites::LEGAL_ENTITY_LTD => 'templates/office/contract.ltd.template.docx',
            Requisites::LEGAL_ENTITY_INDIVIDUAL => 'templates/office/contract.individual.template.docx',
            Requisites::LEGAL_ENTITY_SELF_EMPLOYED => 'templates/office/contract.self_employed.template.docx',
        ]
    ) {
    }

    /**
     * @throws CopyFileException
     * @throws CreateTemporaryFileException
     * @throws Exception
     */
    public function render(string $template, array $data): string
    {
        $template = new TemplateProcessor($template);
        $template->setValues($data);

        ob_start();

        $template->saveAs('php://output');

        return ob_get_clean();
    }

    /**
     * @return array{content: string, extension: string, mimeType: string}
     * @throws CreateTemporaryFileException
     * @throws Exception
     * @throws CopyFileException
     */
    public function generate(Requisites $requisites): array
    {
        if (!$requisites->getLegalEntity()) {
            throw new InvalidArgumentException('Не указана форма юридического лица.');
        }

        /** @var string $template */
        $template = $this->templates[(string)$requisites->getLegalEntity()] ?? '';
        if (!$template) {
            throw new InvalidArgumentException("Не найден шаблон для формы юридического лица '{$requisites->getLegalEntity()}'.");
        }

        $template = rtrim($this->kernel->getProjectDir(), '/') . '/' . ltrim($template, '/');
        $extension = pathinfo($template, PATHINFO_EXTENSION);
        $mimeType = MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? '';

        $taxSystem = $requisites->getTaxSystem();
        $getVatRate = $requisites->getVatRate();
        $label1 = ''; $label2 = ''; $label3 = '';

        if ($requisites->getLegalEntity()==Requisites::LEGAL_ENTITY_INDIVIDUAL) {
            if ($taxSystem == 'usn') {
                if ($getVatRate == 'vat_5') {
                    $label1 = $this->translator->trans('dogovor.individual.nds_1');
                    $label2 = $this->translator->trans('dogovor.individual.nds_2');
                    $label3 = $this->translator->trans('dogovor.individual.usn.nds5.21');
                } elseif ($getVatRate == 'vat_7') {
                    $label1 = $this->translator->trans('dogovor.individual.nds_1');
                    $label2 = $this->translator->trans('dogovor.individual.nds_2');
                    $label3 = $this->translator->trans('dogovor.individual.usn.nds7.21');
                } elseif ($getVatRate == 'vat_20') {
                    $label1 = $this->translator->trans('dogovor.individual.nds_1');
                    $label2 = $this->translator->trans('dogovor.individual.nds_2');
                    $label3 = $this->translator->trans('dogovor.individual.usn.nds20.21');
                } elseif ($getVatRate == 'no_vat') {
                    $label3 = $this->translator->trans('dogovor.individual.beznds.21');
                } elseif ($getVatRate == 'with_vat') {

                }
            } else if ($taxSystem == 'osno') {
                if ($getVatRate == 'no_vat') {

                } elseif ($getVatRate == 'with_vat') {
                    $label1 = $this->translator->trans('dogovor.individual.nds_1');
                    $label2 = $this->translator->trans('dogovor.individual.nds_2');
                    $label3 = $this->translator->trans('dogovor.individual.osno.nds');
                }
            } else {

            }
        } elseif ($requisites->getLegalEntity()==Requisites::LEGAL_ENTITY_LTD) {
            if ($taxSystem == 'usn') {
                if ($getVatRate == 'no_vat') {
                    $label3 = $this->translator->trans('dogovor.ltd.beznds');
                } elseif ($getVatRate == 'with_vat') {

                }
            } else if ($taxSystem == 'osno') {
                if ($getVatRate == 'no_vat') {

                } elseif ($getVatRate == 'with_vat') {
                    $label1 = $this->translator->trans('dogovor.individual.nds_1');
                    $label2 = $this->translator->trans('dogovor.individual.nds_2');
                    $label3 = $this->translator->trans('dogovor.ltd.nds');
                }
            } else {

            }
        }

        $data = [
            'fullName' => $requisites->getFullName(),
            'fullNameGenetive' => $this->morphology->genetiveFullName((string)$requisites->getFullName()) ?: $requisites->getFullName(),
            'fullNameShort' => $this->morphology->fullNameWithInitials((string)$requisites->getFullName()) ?: $requisites->getFullName(),
            'company' => $requisites->getCompany(),
            'charter' => $requisites->getCharter(),
            'charterGenetive' => $this->morphology->genetiveFirstWord((string)$requisites->getCharter()) ?: $requisites->getCharter(),
            'address' => $requisites->getAddress(),
            'legalAddress' => $requisites->getLegalAddress(),
            'mailingAddress' => $requisites->getMailingAddress(),
            'INN' => $requisites->getINN(),
            'KPP' => $requisites->getKPP(),
            'OGRN' => $requisites->getOGRN(),
            'OGRNIP' => $requisites->getOGRNIP()?$requisites->getOGRNIP():$requisites->getOGRN(),
            'SNILS' => $requisites->getSNILS(),
            'passport' => $requisites->getPassport(),
            'bankAccountNumber' => $requisites->getBankAccountNumber(),
            'bank' => $requisites->getBank(),
            'bankCorrespondentAccount' => $requisites->getBankCorrespondentAccount(),
            'BIK' => $requisites->getBIK(),
            'phone' => $requisites->getPhone(),
            'email' => $requisites->getEmail(),
            'label1' => $label1?$label1:'',
            'label2' => $label2?$label2:'',
            'label3' => $label3?$label3:$this->translator->trans('dogovor.individual.usn.empty.21'),
        ];

        return [
            'content' => $this->render($template, $data),
            'extension' => $extension,
            'mimeType' => $mimeType,
        ];
    }
}