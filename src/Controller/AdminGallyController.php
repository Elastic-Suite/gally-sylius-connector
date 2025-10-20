<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Stephan Hochdörfer <S.Hochdoerfer@bitexpert.de>, Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Controller;

use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Config\TokenCacheManager;
use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Gally\SyliusPlugin\Form\Type\GallyConfigurationType;
use Gally\SyliusPlugin\Form\Type\SyncSourceFieldsType;
use Gally\SyliusPlugin\Form\Type\TestConnectionType;
use Gally\SyliusPlugin\Indexer\Provider\ProviderInterface;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AdminGallyController extends AbstractController
{
    /** @var ProviderInterface[] */
    protected array $providers;

    /** @var array<string, string> */
    protected array $syncMethod = [
        'catalog' => 'syncAllLocalizedCatalogs',
        'sourceField' => 'syncAllSourceFields',
        'sourceFieldOption' => 'syncAllSourceFieldOptions',
    ];

    public function __construct(
        private GallyConfigurationRepository $gallyConfigurationRepository,
        protected StructureSynchonizer $synchonizer,
        protected ConfigManager $configManager,
        \IteratorAggregate $providers,
        private TranslatorInterface $translator,
        private TokenCacheManager $tokenCacheManager,
    ) {
        /** @var ProviderInterface[] $providersArray */
        $providersArray = iterator_to_array($providers);
        $this->providers = $providersArray;
    }

    public function renderGallyConfigForm(Request $request): Response
    {
        $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
        $configForm = $this->createForm(GallyConfigurationType::class, $gallyConfiguration);
        $configForm->handleRequest($request);

        if ($configForm->isSubmitted() && $configForm->isValid()) {
            /** @var GallyConfiguration $gallyConfiguration */
            $gallyConfiguration = $configForm->getData();

            $this->gallyConfigurationRepository->add($gallyConfiguration);
            $this->addFlash('success', $this->translator->trans('gally_sylius.ui.configuration_saved'));
            $this->tokenCacheManager->clearCache();
        }

        return $this->render('@GallySyliusPlugin/admin/gally/index.html.twig', [
            'connectionForm' => $configForm->createView(),
            'testForm' => $this->createForm(TestConnectionType::class)->createView(),
            'syncForm' => $this->createForm(SyncSourceFieldsType::class)->createView(),
        ]);
    }

    public function renderTestConnectionForm(Request $request): Response
    {
        $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
        $testForm = $this->createForm(TestConnectionType::class);
        $testForm->handleRequest($request);

        if ($testForm->isSubmitted() && $testForm->isValid()) {
            try {
                $this->configManager->testCredentials();
                $this->addFlash('success', $this->translator->trans('gally_sylius.ui.test_connection_success'));
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->translator->trans('gally_sylius.ui.test_connection_failure') . ' ' . $e->getMessage()
                );
            }
        }

        return $this->render('@GallySyliusPlugin/admin/gally/index.html.twig', [
            'connectionForm' => $this->createForm(GallyConfigurationType::class, $gallyConfiguration)->createView(),
            'testForm' => $testForm->createView(),
            'syncForm' => $this->createForm(SyncSourceFieldsType::class)->createView(),
        ]);
    }

    public function renderSyncFieldsForm(Request $request): Response
    {
        $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
        $syncForm = $this->createForm(SyncSourceFieldsType::class);
        $syncForm->handleRequest($request);

        if ($syncForm->isSubmitted() && $syncForm->isValid()) {
            $validConnection = true;
            try {
                $this->configManager->testCredentials();
            } catch (\Exception $e) {
                $validConnection = false;
                $this->addFlash(
                    'error',
                    $this->translator->trans('gally_sylius.ui.test_connection_failure') . ' ' . $e->getMessage()
                );
            }

            if ($validConnection) {
                foreach ($this->syncMethod as $entity => $method) {
                    $this->synchonizer->{$method}($this->providers[$entity]->provide());
                }
                $this->addFlash('success', $this->translator->trans('gally_sylius.ui.sync_success'));
            }
        }

        return $this->render('@GallySyliusPlugin/admin/gally/index.html.twig', [
            'connectionForm' => $this->createForm(GallyConfigurationType::class, $gallyConfiguration)->createView(),
            'testForm' => $this->createForm(TestConnectionType::class)->createView(),
            'syncForm' => $syncForm->createView(),
        ]);
    }
}
