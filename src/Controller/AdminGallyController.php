<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Controller;

use Gally\Rest\ApiException;
use Gally\SyliusPlugin\Api\AuthenticationTokenProvider;
use Gally\SyliusPlugin\Form\Type\GallyConfigurationType;
use Gally\SyliusPlugin\Form\Type\SyncSourceFieldsType;
use Gally\SyliusPlugin\Form\Type\TestConnectionType;
use Gally\SyliusPlugin\Repository\GallyConfigurationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminGallyController extends AbstractController
{
    public function __construct(
        private GallyConfigurationRepository $gallyConfigurationRepository,
        private AuthenticationTokenProvider $authenticationTokenProvider,
    ) {
    }

    public function renderGallyConfigForm(Request $request): Response
    {
        $gallyConfiguration = $this->gallyConfigurationRepository->getConfiguration();
        $configForm = $this->createForm(GallyConfigurationType::class, $gallyConfiguration);
        $configForm->handleRequest($request);

        if($configForm->isSubmitted() && $configForm->isValid()) {
            $gallyConfiguration = $configForm->getData();

            $this->gallyConfigurationRepository->add($gallyConfiguration);
            $this->addFlash('success', 'Connection configuration stored!');
        }

        return $this->render('@GallySyliusPlugin/Config/_form.html.twig', [
            'connectionForm' => $configForm->createView(),
            'testForm' => $this->createForm(TestConnectionType::class),
            'syncForm' => $this->createForm(SyncSourceFieldsType::class),
        ]);
    }

    public function renderTestConnectionForm(Request $request): Response
    {
        $testForm = $this->createForm(TestConnectionType::class);
        $testForm->handleRequest($request);

        if($testForm->isSubmitted() && $testForm->isValid()) {
            try {
                $configuration = $this->gallyConfigurationRepository->getConfiguration();
                $this->authenticationTokenProvider->getAuthenticationToken(
                    $configuration->getBaseUrl(),
                    $configuration->getUserName(),
                    $configuration->getPassword(),
                );
                $this->addFlash('success', 'Connection test successful!');
            } catch (ApiException $e) {
                $this->addFlash('error', 'Connection test failed! Wrong credentials?');
            }
        }

        return $this->render('@GallySyliusPlugin/Config/_form.html.twig', [
            'connectionForm' => $this->createForm(GallyConfigurationType::class),
            'testForm' => $testForm,
            'syncForm' => $this->createForm(SyncSourceFieldsType::class)
        ]);
    }

    public function renderSyncFieldsForm(Request $request): Response
    {
        $syncForm = $this->createForm(SyncSourceFieldsType::class);
        $syncForm->handleRequest($request);

        if($syncForm->isSubmitted() && $syncForm->isValid()) {
            $this->addFlash('success', 'Attribute sync successful!');
        }

        return $this->render('@GallySyliusPlugin/Config/_form.html.twig', [
            'connectionForm' =>  $this->createForm(GallyConfigurationType::class),
            'testForm' => $this->createForm(TestConnectionType::class),
            'syncForm' => $syncForm,
        ]);
    }
}
