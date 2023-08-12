<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminGallyController extends AbstractController
{
    private const CONNECTION_INITIAL = 'initial';
    private const CONNECTION_SUCCESS = 'success';
    private const CONNECTION_FAILED = 'failed';
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function renderGallyConfig(Request $request): Response
    {
        return $this->render('@GallySyliusPlugin/Config/_form.html.twig', []);
    }
}
