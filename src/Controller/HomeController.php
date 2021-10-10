<?php

namespace App\Controller;

use App\Service\GenomeInterpreter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(GenomeInterpreter $genomeDecoder): Response
    {
        $hexGenome = "21a068c2|9542f0c0|2e1052a9|30013a63";

        $neuralNetwork = $genomeDecoder->buildNeuralNetwork($hexGenome);

        return $this->render('home/index.html.twig', [
            'hex_genome' => $hexGenome,
            'neural_network' => $neuralNetwork,
        ]);
    }
}
