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
        $hexGenome = "2353fc85|1cf85f26|4b44e136|b2694511";

        $neuralNetwork = $genomeDecoder->buildNeuralNetwork($hexGenome);
        $neuralGraphData = $genomeDecoder->getNeuralGraphData($neuralNetwork);

        return $this->render('home/index.html.twig', [
            'hex_genome' => $hexGenome,
            'neural_network' => $neuralNetwork,
            'neural_graph_data' => $neuralGraphData,
        ]);
    }
}
