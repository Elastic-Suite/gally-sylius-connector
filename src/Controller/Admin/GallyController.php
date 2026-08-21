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

namespace Gally\SyliusPlugin\Controller\Admin;

use Gally\Sdk\Client\Client;
use Gally\Sdk\Repository\RecommenderTypeRepository;
use Gally\Sdk\Service\BundleManager;
use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Config\ConfigManager;
use Gally\SyliusPlugin\Entity\GallyConfiguration;
use Gally\SyliusPlugin\Form\Type\ClearCacheType;
use Gally\SyliusPlugin\Form\Type\GallyConfigurationType;
use Gally\SyliusPlugin\Form\Type\SyncSourceFieldsType;
use Gally\SyliusPlugin\Form\Type\TestConnectionType;
use Gally\SyliusPlugin\Indexer\Provider\ProviderInterface;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Gally\SyliusPlugin\Service\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GallyController extends AbstractController
{
    /** @var array<string, ProviderInterface[]> */
    protected array $providers = [];

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
        private CacheManager $cacheManager,
    ) {
        /** @var ProviderInterface $provider */
        foreach ($providers as $provider) {
            $this->providers[$provider->getEntity()][] = $provider;
        }
    }

    /**
     * Merges the provide() results of every provider registered for the given entity,
     * so several namespaces can contribute to it independently.
     */
    private function provideAll(string $entity): iterable
    {
        foreach ($this->providers[$entity] ?? [] as $provider) {
            yield from $provider->provide();
        }
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
            $this->cacheManager->clearCache(Client::API_TOKEN_CACHE_KEY);
        }

        return $this->render('@GallySyliusPlugin/admin/gally/index.html.twig', [
            'connectionForm' => $configForm->createView(),
            'testForm' => $this->createForm(TestConnectionType::class)->createView(),
            'syncForm' => $this->createForm(SyncSourceFieldsType::class)->createView(),
            'clearCacheForm' => $this->createForm(ClearCacheType::class)->createView(),
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
            } catch (\Throwable $e) {
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
            'clearCacheForm' => $this->createForm(ClearCacheType::class)->createView(),
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
            } catch (\Throwable $e) {
                $validConnection = false;
                $this->addFlash(
                    'error',
                    $this->translator->trans('gally_sylius.ui.test_connection_failure') . ' ' . $e->getMessage()
                );
            }

            if ($validConnection) {
                foreach ($this->syncMethod as $entity => $method) {
                    // @phpstan-ignore method.dynamicName
                    $this->synchonizer->{$method}($this->provideAll($entity));
                }
                $this->addFlash('success', $this->translator->trans('gally_sylius.ui.sync_success'));
            }
        }

        return $this->render('@GallySyliusPlugin/admin/gally/index.html.twig', [
            'connectionForm' => $this->createForm(GallyConfigurationType::class, $gallyConfiguration)->createView(),
            'testForm' => $this->createForm(TestConnectionType::class)->createView(),
            'syncForm' => $syncForm->createView(),
            'clearCacheForm' => $this->createForm(ClearCacheType::class)->createView(),
        ]);
    }

    public function renderClearCacheForm(Request $request): Response
    {
        $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
        $clearCacheForm = $this->createForm(ClearCacheType::class);
        $clearCacheForm->handleRequest($request);

        if ($clearCacheForm->isSubmitted() && $clearCacheForm->isValid()) {
            $this->cacheManager->clearCache(Client::API_TOKEN_CACHE_KEY);
            $this->cacheManager->clearCache(BundleManager::BUNDLES_CACHE_KEY);
            $this->cacheManager->clearCache(RecommenderTypeRepository::RECOMMENDER_TYPES_CACHE_KEY);
            $this->addFlash('success', $this->translator->trans('gally_sylius.ui.clear_cache_success'));
        }

        return $this->render('@GallySyliusPlugin/admin/gally/index.html.twig', [
            'connectionForm' => $this->createForm(GallyConfigurationType::class, $gallyConfiguration)->createView(),
            'testForm' => $this->createForm(TestConnectionType::class)->createView(),
            'syncForm' => $this->createForm(SyncSourceFieldsType::class)->createView(),
            'clearCacheForm' => $clearCacheForm->createView(),
        ]);
    }
}
