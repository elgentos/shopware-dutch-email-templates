<?php declare(strict_types=1);

namespace ElgentosDutchEmailTemplates\Command;

use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImageImport
 * @package ElgentosDutchEmailTemplates\Command
 */
class TemplateImport extends Command
{
    public EntityRepository $languageRepository;

    public EntityRepository $mailTemplateRepository;

    public EntityRepository $mailTemplateTranslationRepository;

    public EntityRepository $mailTemplateTypeRepository;

    public string $basePath = '';

    public function __construct(
        EntityRepository $languageRepository,
        EntityRepository $mailTemplateRepository,
        EntityRepository $mailTemplateTranslationRepository,
        EntityRepository $mailTemplateTypeRepository,
        string $name = null
    )
    {
        parent::__construct($name);
        $this->languageRepository = $languageRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailTemplateTranslationRepository = $mailTemplateTranslationRepository;
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
    }

    protected function configure(): void
    {
        $this->addOption('languageName', 'l', InputOption::VALUE_OPTIONAL, 'Language name', 'Nederlands');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $languageName = $input->getOption('languageName');

        // Find language based on language name
        $criteria = new Criteria();
        $criteria->addAssociation('locale');
        $criteria->addFilter(new EqualsFilter('name', $languageName));
        $context = Context::createDefaultContext();
        /** @var LanguageEntity $language */
        try {
            $language = $this->languageRepository->search($criteria, $context)->first();
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not find mail language with name %s</error>', $languageName));
            return 1;
        }

        $languageCode = $language->getLocale() ? $language->getLocale()->getCode() : null;
        if (!$languageCode) {
            $output->writeln(sprintf('<error>Could not find mail language locale with name %s</error>', $languageName));
            return 1;
        }

        // Filter out non-directories or relatives
        $this->basePath = __DIR__ . '/../Resources/views/email/' . $languageCode . '/';
        $mailTypes = array_filter(scandir($this->basePath), function ($input) {
            return strlen($input) > 2 && is_dir($this->basePath . $input);
        });

        // Loop through  mail types to add the templates
        foreach ($mailTypes as $mailTypeTechnicalName) {
            try {
                $mailTemplateType = $this->getMailTemplateTypeByTechnicalName($mailTypeTechnicalName, $context);
                $mailTemplate = $mailTemplateType ? $this->getMailTemplateByMailTemplateTypeId($mailTemplateType, $context) : null;
                $mailTemplateContent = $this->getMailTemplateContent($mailTemplate, $mailTypeTechnicalName, $mailTemplateType->getId(), $language);
                if (empty($mailTemplateContent)) {
                    $output->writeln(sprintf('<comment>No HTML and/or text content for %s (%s) found. Skipping.</comment>', $mailTypeTechnicalName, $languageName));
                    continue;
                }
                try {
                    // If the 'id' field is set, create a new mail template. If the 'mailTemplateId' is set, create a new mail translation template
                    if (isset($mailTemplateContent['id'])) {
                        $this->mailTemplateRepository->upsert([$mailTemplateContent], $context);
                        $output->writeln(sprintf('<info>Succesfully upserted mail template for %s.</info>', $mailTypeTechnicalName));
                    } elseif (isset($mailTemplateContent['mailTemplateId'])) {
                        $mailTemplateContent['languageId'] = $language->getId();
                        $this->mailTemplateTranslationRepository->upsert([$mailTemplateContent], $context);
                        $output->writeln(sprintf('<info>Succesfully upserted mail template translation for %s.</info>', $mailTypeTechnicalName));
                    }
                } catch (\Exception $e) {
                    $output->writeln(sprintf('<error>Could not upsert mail template for %s; %s.</error>', $mailTypeTechnicalName, $e->getMessage()));
                }
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Could not find mail template type for %s; %s</error>', $mailTypeTechnicalName, $e->getMessage()));
            }
        }

        return 0;
    }

    private function getMailTemplateContent(?MailTemplateEntity $mailTemplate, string $mailTypeTechnicalName, string $mailTypeId, LanguageEntity $language): array
    {
        $contentHtml = @file_get_contents($this->basePath . $mailTypeTechnicalName . '/html.twig');
        $contentText = @file_get_contents($this->basePath . $mailTypeTechnicalName . '/plain.twig');
        $subject = @file_get_contents($this->basePath . $mailTypeTechnicalName . '/subject.twig');

        if (!$contentHtml || !$contentText) {
            return [];
        }

        $data = [
            'description' => $mailTypeTechnicalName . ' (' . $language->getName() . ')',
            'systemDefault' => true,
            'senderName' => '{{ salesChannel.name }}',
            'subject' => $subject ? trim($subject) : $mailTypeTechnicalName,
            'contentHtml' => $contentHtml,
            'contentPlain' => $contentText,
            'mailTemplateTypeId' => $mailTypeId,
        ];

        // If the mail template already exists, pass along the mail template ID, otherwise create a new UUID
        if ($mailTemplate) {
            $data['mailTemplateId'] = $mailTemplate->getId();
        } else {
            $data['id'] = Uuid::randomHex();
        }

        return $data;
    }

    protected function getMailTemplateTypeByTechnicalName(string $mailTypeTechnicalName, Context $context): ?MailTemplateTypeEntity
    {
        $mailTemplateTypeCriteria = new Criteria();
        $mailTemplateTypeCriteria->addFilter(new EqualsFilter('technicalName', $mailTypeTechnicalName));
        return $this->mailTemplateTypeRepository->search($mailTemplateTypeCriteria, $context)->first();
    }

    protected function getMailTemplateByMailTemplateTypeId(MailTemplateTypeEntity $mailTemplateType, Context $context): ?MailTemplateEntity
    {
        $mailTemplateCriteria = new Criteria();
        $mailTemplateCriteria->addFilter(new EqualsFilter('mailTemplateTypeId', $mailTemplateType->getId()));
        return $this->mailTemplateRepository->search($mailTemplateCriteria, $context)->first();
    }
}
