<?php

namespace App\Controller;

use App\Entity\Cursos;
use App\Form\CursosType;
use App\Repository\AcademicoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cursos")
 */
class CursosController extends AbstractController
{
    /**
     * @Route("/anual/{actual}", name="cursos_index", methods={"GET"}, defaults={"actual"="2024"})
     */
    public function index($actual)
    {

        $em = $this->getDoctrine()->getManager();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');


        $user = $this->getUser();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $user->getAcademico());

        $cursos = $informe->getCursos();
        $enviado = $informe->isEnviado();


        return $this->render('cursos/index.html.twig', array(
            'cursos' => $cursos,
            'enviado'=>$enviado,
            'anual'=>$actual,
        ));
    }

    /**
     * Creates a new Cursos entity.
     *
     * @Route("/new", name="cursos_new", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        $securityContext = $this->container->get('security.token_storage');

        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $twigglobals = $this->get("twig")->getGlobals();
        $actual = $twigglobals["actual"];

        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);

        $curso = new Cursos();
        $form = $this->createForm('App\Form\CursosType', $curso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // Access form fields
            $formInicio = $form->get('fechaInicio')->getData();
            $formFin = $form->get('fechaFin')->getData();


            $fechaInicio = new \DateTime("$actual-$formInicio-01");
            $fechaFin = new \DateTime("$actual-$formFin-30");

            $curso->setFechaInicio($fechaInicio);
            $curso->setFechaFin($fechaFin);

            $curso->setInforme($informe);
            $em->persist($curso);
            $em->flush();

            //return $this->redirectToRoute('cursos_show', array('id' => $curso->getId()));
            return $this->redirectToRoute('cursos_index');

        }

        return $this->render('cursos/new.html.twig', array(
            'curso' => $curso,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Cursos entity.
     *
     * @Route("/{id}", name="cursos_show", methods={"GET"})
     */
    public function show(Cursos $curso)
    {
        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $twigglobals = $this->get("twig")->getGlobals();
        $actual = $twigglobals["actual"];

        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);
        $enviado = $informe->isEnviado();

        $deleteForm = $this->createDeleteForm($curso);

        // check for "view" access: calls all voters
//        $this->denyAccessUnlessGranted('view', $curso);


        return $this->render('cursos/show.html.twig', array(
            'curso' => $curso,
            'enviado'=>$enviado,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Cursos entity.
     *
     * @Route("/{id}/edit", name="cursos_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Cursos $curso)
    {
        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $deleteForm = $this->createDeleteForm($curso);
        $fechaInicio = $curso->getFechaInicio();
        $fechaFin = $curso->getFechaFin();
        $monthInicio = $fechaInicio->format('n'); // 'n' gives you the month without leading zeros
        $monthFin = $fechaFin->format('n');

        $editForm = $this->createForm('App\Form\CursosType', $curso);

        $editForm->get('fechaFin')->setData($monthFin);
        $editForm->get('fechaInicio')->setData($monthInicio);


        $editForm->remove('lugares');
        $editForm->remove('lugar');



        $editForm->add('lugar','Symfony\Component\Form\Extension\Core\Type\TextType', array('label' => 'Lugar donde se impartió'));



        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // Access form fields
            $formInicio = $editForm->get('fechaInicio')->getData();
            $formFin = $editForm->get('fechaFin')->getData();
            $twigglobals = $this->get("twig")->getGlobals();
            $actual = $twigglobals["actual"];

            $fechaInicio = new \DateTime("$actual-$formInicio-01");
            $fechaFin = new \DateTime("$actual-$formFin-30");

            $curso->setFechaInicio($fechaInicio);
            $curso->setFechaFin($fechaFin);

            $em->persist($curso);
            $em->flush();

            //return $this->redirectToRoute('cursos_edit', array('id' => $curso->getId()));
            return $this->redirectToRoute('cursos_index');

        }

        return $this->render('cursos/edit.html.twig', array(
            'id'=>$curso->getId(),
            'curso' => $curso,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Cursos entity.
     *
     * @Route("/{id}", name="cursos_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Cursos $curso)
    {
        $form = $this->createDeleteForm($curso);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($curso);
            $em->flush();
        }

        return $this->redirectToRoute('cursos_index');
    }

    /**
     * Creates a form to delete a Cursos entity.
     *
     * @param Cursos $curso The Cursos entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Cursos $curso)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('cursos_delete', array('id' => $curso->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}