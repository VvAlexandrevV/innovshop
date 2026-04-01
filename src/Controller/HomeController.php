<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {

        $produitsALaUne = $productRepository->findBy(
            ['aLaUne' => true], //filtre
            null,               //pas d ordre specifique
            3                   //limite le resultat a 3
        );

        $derniersProduits = $productRepository->findBy(
            [],                      //aucun filtre(tout les produits)
            ['createdAt' => 'DESC'], //du plus recent au plus ancien
            3                        //les 3 premiers   
        );

        return $this->render('home/index.html.twig', [
            'produitsALaUne' => $produitsALaUne,
            'derniersProduits' => $derniersProduits,
        ]);
    }
}
