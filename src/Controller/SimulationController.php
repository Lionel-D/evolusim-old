<?php

namespace App\Controller;

use App\Entity\Simulation;
use App\Form\SimulationType;
use App\Repository\SimulationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/simulation")
 */
class SimulationController extends AbstractController
{
    /**
     * @Route("/", name="simulation_index", methods={"GET"})
     */
    public function index(SimulationRepository $simulationRepository): Response
    {
        return $this->render('simulation/index.html.twig', [
            'simulations' => $simulationRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="simulation_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $simulation = new Simulation();
        $form = $this->createForm(SimulationType::class, $simulation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($simulation);
            $entityManager->flush();

            return $this->redirectToRoute('simulation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('simulation/new.html.twig', [
            'simulation' => $simulation,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="simulation_show", methods={"GET"})
     */
    public function show(Simulation $simulation): Response
    {
        return $this->render('simulation/show.html.twig', [
            'simulation' => $simulation,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="simulation_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Simulation $simulation): Response
    {
        $form = $this->createForm(SimulationType::class, $simulation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('simulation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('simulation/edit.html.twig', [
            'simulation' => $simulation,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="simulation_delete", methods={"POST"})
     */
    public function delete(Request $request, Simulation $simulation): Response
    {
        if ($this->isCsrfTokenValid('delete'.$simulation->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($simulation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('simulation_index', [], Response::HTTP_SEE_OTHER);
    }
}
