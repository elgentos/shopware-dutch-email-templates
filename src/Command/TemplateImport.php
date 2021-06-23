<?php declare(strict_types=1);

namespace ElgentosDutchEmailTemplates\Command;

use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\TabularDataReader;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImageImport
 * @package ElgentosDutchEmailTemplates\Command
 */
class TemplateImport extends Command
{
    /**
     * @var ContainerInterface
     */
    public ContainerInterface $container;
    /**
     * @var EntityRepositoryInterface
     */
    public EntityRepositoryInterface $languageRepository;
    /**
     * @var EntityRepositoryInterface
     */
    public EntityRepositoryInterface $mailTemplateRepository;
    /**
     * @var EntityRepositoryInterface
     */
    public EntityRepositoryInterface $mailTemplateTranslationRepository;
    /**
     * @var EntityRepositoryInterface
     */
    public EntityRepositoryInterface $mailTemplateTypeRepository;

    /**
     * @var MailTemplateDefinition
     */
    public MailTemplateDefinition $mailTemplateDefinition;

    public string $basePath = '';

    public function __construct(
        ContainerInterface $container,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        EntityRepositoryInterface $mailTemplateTranslationRepository,
        EntityRepositoryInterface $mailTemplateTypeRepository,
        MailTemplateDefinition $mailTemplateDefinition,
        string $name = null
    ) {
        parent::__construct($name);
        $this->container = $container;
        $this->languageRepository = $languageRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailTemplateTranslationRepository = $mailTemplateTranslationRepository;
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->mailTemplateDefinition = $mailTemplateDefinition;
    }

    /**
     * Configure the input and outputs
     */
    protected function configure() : void
    {
        $this
            ->addOption('languageName', 'l', InputOption::VALUE_OPTIONAL, 'Language name', 'Nederlands')
            ->addOption('languageCode', 'c', InputOption::VALUE_OPTIONAL, 'Language code', 'nl-NL')
            ->addOption('overwrite', 'o', InputOption::VALUE_OPTIONAL, 'Overwrite existing templates', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $overwrite = !!$input->getOption('overwrite');
        $languageName = $input->getOption('languageName');
        $languageCode = $input->getOption('languageCode');

        // Find language based on language name
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $languageName));
        $context = Context::createDefaultContext();
        /** @var LanguageEntity $language */
        try {
            $language = $this->languageRepository->search($criteria, $context)->first();
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not find mail language with name %s</error>', $languageName));
            return 1;
        }

        // Filter out non-directories or relatives
        $this->basePath = __DIR__ . '/../Resources/views/email/' . $languageCode . '/';
        $mailTypes = array_filter(scandir($this->basePath), function ($input) {
            return strlen($input) > 2 && is_dir($this->basePath . $input);
        });

        // Loop through  mailtypes to add the templates
        foreach ($mailTypes as $mailTypeTechnicalName) {
            $mailTemplateTypeCriteria = new Criteria();
            $mailTemplateTypeCriteria->addFilter(new EqualsFilter('technicalName', $mailTypeTechnicalName));
            try {
                $mailType = $this->mailTemplateTypeRepository->search($mailTemplateTypeCriteria, $context)->first();
                $mailTemplateCriteria = new Criteria();
                $mailTemplateCriteria->addFilter(new EqualsFilter('mailTemplateTypeId', $mailType->getId()));
                $mailTemplate = $this->mailTemplateRepository->search($mailTemplateCriteria, $context)->first();
                if (!$mailTemplate) {
                    $mailTemplateId = Uuid::randomHex();
                } else {
                    $mailTemplateId = $mailTemplate->getId();
                }

                $mailTemplate = $this->getMailTemplateContent($mailTemplateId, $mailTypeTechnicalName, $mailType->getId(), $languageName, $languageCode);
                if (!$mailTemplate) {
                    $output->writeln(sprintf('<comment>No HTML and/or text content for %s (%s) found. Skipping.</comment>', $mailTypeTechnicalName, $languageName));
                    continue;
                }
                if ($overwrite) {
                    try {
                        $this->mailTemplateTranslationRepository->upsert([$mailTemplate], $context);
                        $output->writeln(sprintf('<info>Succesfully upserted mail template for %s.</info>', $mailTypeTechnicalName));
                    } catch (\Exception $e) {
                        $output->writeln(sprintf('<error>Could not upsert mail template for %s; %s.</error>', $mailTypeTechnicalName, $e->getMessage()));
                    }
                } else {
                    try {
                        $this->mailTemplateTranslationRepository->create([$mailTemplate], $context);
                        $output->writeln(sprintf('<info>Succesfully inserted mail template for %s.</info>', $mailTypeTechnicalName));
                    } catch (\Exception $e) {
                        $output->writeln(sprintf('<error>Could not insert mail template for %s; %s.</error>', $mailTypeTechnicalName, $e->getMessage()));
                    }
                }
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Could not find mail template type for %s; %s</error>', $mailTypeTechnicalName, $e->getMessage()));
            }
        }

        return 0;
    }

    private function getMailTemplateContent($mailTemplateId, string $mailTypeTechnicalName, $mailTypeId, string $languageName, string $languageCode)
    {
        $contentHtml = @file_get_contents($this->basePath . $mailTypeTechnicalName . '/html.twig');
        $contentText = @file_get_contents($this->basePath . $mailTypeTechnicalName . '/plain.twig');
        $subject = @file_get_contents($this->basePath . $mailTypeTechnicalName . '/subject.twig');

        if (!$contentHtml || !$contentText) {
            return false;
        }

        return [
            'id' => $mailTemplateId,
            'description' => $mailTypeTechnicalName . ' (' . $languageName . ')',
            'systemDefault' => true,
            'senderName' => '{{ salesChannel.name }}',
            'subject' => $subject ? trim($subject) : $mailTypeTechnicalName,
            'contentHtml' => $contentHtml,
            'contentPlain' => $contentText,
            'mailTemplateTypeId' => $mailTypeId,
        ];
    }
}
