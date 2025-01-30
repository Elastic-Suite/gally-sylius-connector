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

use Gally\SyliusPlugin\Api\AuthenticationTokenProvider;
use Gally\SyliusPlugin\Form\Type\GallyConfigurationType;
use Gally\SyliusPlugin\Form\Type\SyncSourceFieldsType;
use Gally\SyliusPlugin\Form\Type\TestConnectionType;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Gally\SyliusPlugin\Synchronizer\SourceFieldSynchronizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AdminGallyController extends AbstractController
{
    public function __construct(
        private GallyConfigurationRepository $gallyConfigurationRepository,
        private AuthenticationTokenProvider $authenticationTokenProvider,
        private SourceFieldSynchronizer $sourceFieldSynchronizer,
        private TranslatorInterface $translator,
    ) {
    }

    public function renderGallyConfigForm(Request $request): Response
    {
        $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
        $configForm = $this->createForm(GallyConfigurationType::class, $gallyConfiguration);
        $configForm->handleRequest($request);

        if ($configForm->isSubmitted() && $configForm->isValid()) {
            $gallyConfiguration = $configForm->getData();

            $this->gallyConfigurationRepository->add($gallyConfiguration);
            $this->addFlash('success', $this->translator->trans('gally_sylius.ui.configuration_saved'));
        }

        return $this->render('@GallySyliusPlugin/Config/_form.html.twig', [
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
                $configuration = $this->gallyConfigurationRepository->getConfiguration();
                $this->authenticationTokenProvider->getAuthenticationToken(
                    $configuration->getBaseUrl(),
                    $configuration->getUserName(),
                    $configuration->getPassword(),
                );
                $this->addFlash('success', $this->translator->trans('gally_sylius.ui.test_connection_success'));
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->translator->trans('gally_sylius.ui.test_connection_failure') . ' ' . $e->getMessage()
                );
            }
        }

        return $this->render('@GallySyliusPlugin/Config/_form.html.twig', [
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
            $this->sourceFieldSynchronizer->synchronizeAll();

            $this->addFlash('success', $this->translator->trans('gally_sylius.ui.sync_success'));
        }

        return $this->render('@GallySyliusPlugin/Config/_form.html.twig', [
            'connectionForm' => $this->createForm(GallyConfigurationType::class, $gallyConfiguration)->createView(),
            'testForm' => $this->createForm(TestConnectionType::class)->createView(),
            'syncForm' => $syncForm->createView(),
        ]);
    }
}
