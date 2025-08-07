<?php

namespace App\Controller;

use App\Entity\Eventos;
use App\Form\EventosType;
use App\Repository\AcademicoRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Eventos controller.
 *
 * @Route("/eventos")
 */
class EventosController extends AbstractController
{
    /**
     * Lists all Eventos entities.
     *
     * @Route("/", name="eventos_index", methods={"GET"})
     * @Route("/anual/{actual}", name="eventos_index", methods={"GET"}, defaults={"actual"="2025"})

     */
    public function index($actual)
    {

        $em = $this->getDoctrine()->getManager();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $user->getAcademico());
        $academicoId = $informe->getAcademico()->getIdsia();

        $eventos = $em->getRepository('App:Eventos')->findEventos($informe->getId());

        $enviado = $informe->isEnviado();

        return $this->render('eventos/index.html.twig', array(
            'eventos' => $eventos,
            'enviado'=>$enviado,
            'titulo' => 'Licencias, comisiones',
            'anual'=>$actual,
            'academicoId' => $academicoId,
            'informe' => $informe,
        ));
    }

//    /**
//     * Lists all Eventos entities.
//     *
//     * @Route("/visitas", name="visitas_index")
//     * @Method("GET")
//     */
//    public function visitasAction()
//    {
//
//        $em = $this->getDoctrine()->getManager();
//
//        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
//            throw $this->createAccessDeniedException();
//        }
//
//        $user = $this->get('security.context')->getToken()->getUser();
//        $informe = $em->getRepository('App:Informe')->findOneByAnio(2018, $user->getAcademico());
//
//        $visitantes = $em->getRepository('App:Eventos')->findByVisitantes($informe->getId());
//
//        $enviado = $informe->isEnviado();
//
//
//        return $this->render('eventos/index.html.twig', array(
//            'eventos' => $visitantes,
//            'enviado'=>$enviado,
//            'titulo' => 'Visitantes',
//        ));
//
//    }

    /**
     * Creates a new Eventos entity.
     *
     * @Route("/new", name="eventos_new", methods={"GET","POST"})
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

        $evento = new Eventos();
        $form = $this->createForm('App\Form\EventosType', $evento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $evento->setInforme($informe);
            $em->persist($evento);
            $em->flush();

            if ($evento->getTipo() == 'Visitante') {
//                return $this->redirectToRoute('visitas_index');
            }
            else {
                //return $this->redirectToRoute('eventos_show', array('id' => $evento->getId()));
                return $this->redirectToRoute('eventos_index');
            }

        }

        return $this->render('eventos/new.html.twig', array(
            'evento' => $evento,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Eventos entity.
     *
     * @Route("/{id}", name="eventos_show", methods={"GET"})
     */
    public function show(Eventos $evento)
    {
        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $twigglobals = $this->get("twig")->getGlobals();
        $actual = $twigglobals["actual"];

        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);
        $enviado = $informe->isEnviado();

        $deleteForm = $this->createDeleteForm($evento);

        // check for "view" access: calls all voters
//        $this->denyAccessUnlessGranted('view', $evento);


        return $this->render('eventos/show.html.twig', array(
            'evento' => $evento,
            'enviado'=>$enviado,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Eventos entity.
     *
     * @Route("/{id}/edit", name="eventos_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Eventos $evento)
    {
        $deleteForm = $this->createDeleteForm($evento);
//        $this->denyAccessUnlessGranted('edit', $evento);

        $editForm = $this->createForm('App\Form\EventosType', $evento);



        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($evento);
            $em->flush();

            if ($evento->getTipo() == 'Visitante') {
                return $this->redirectToRoute('visitas_index');
            }
            else {
                //return $this->redirectToRoute('eventos_show', array('id' => $evento->getId()));
                return $this->redirectToRoute('eventos_index');
            }

        }

        return $this->render('eventos/edit.html.twig', array(
            'id'=>$evento->getId(),
            'evento' => $evento,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Eventos entity.
     *
     * @Route("/{id}", name="eventos_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Eventos $evento)
    {
        $form = $this->createDeleteForm($evento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($evento);
            $em->flush();
        }

        return $this->redirectToRoute('eventos_index');
    }

    /**
     * Creates a form to delete a Eventos entity.
     *
     * @param Eventos $evento The Eventos entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Eventos $evento)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('eventos_delete', array('id' => $evento->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/import-sia/{academicoId}/{year}", name="import_sia")
     */
    public function importData(EntityManagerInterface $em, $academicoId, $year): Response
    {
        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $twigglobals = $this->get("twig")->getGlobals();
        $actual = $twigglobals["actual"];

        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);

        $url = 'https://gaspacho.matmor.unam.mx/sia/app_dev.php/export/'.$academicoId.'/'.$year;

        // Create an HTTP client instance
        $client = HttpClient::create();

        try {
            // Fetch JSON data from the API
            $response = $client->request('GET', $url);
            $data = $response->toArray();
        } catch (JsonException $e) {
            return new Response('Failed to fetch data: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Process the JSON response and populate Doctrine entities
        $count = 0; // To track the number of records imported
        $visitantes = 0;
        $licencias = 0;
        $comisiones = 0;

        foreach ($data as $item) {
            // Ensure the JSON structure matches your entity fields
            $evento = new Eventos();

            if( $item['tipo'] == 'Visitante' ){
                $visitantes++;
            }
            if( $item['tipo'] == 'Licencia' ){
                $licencias++;
            }
            if( $item['tipo'] == 'Comisión' ){
                $comisiones++;
            }
            $evento->setTipo($item['tipo']);

            if ($item['motivo'] !=null) {
                $evento->setMotivo($item['motivo']);
            }

            $evento->setTipoActividad($item['tipoActividad']);
            $evento->setNombreEvento($item['nombreEvento']);
            $evento->setNacional($item['nacional']);
            $evento->setInvitacion($item['invitacion']);
            $evento->setPlenaria($item['plenaria']);
            $evento->setInvitacion($item['invitacion']);
            $evento->setSinodalAlumno($item['sinodalAlumno']);
            $evento->setSinodalGrado($item['sinodalGrado']);
            $evento->setLugar($item['lugar']);
            $evento->setDepartamento($item['departamento']);
            $evento->setInstitucion($item['institucion']);
            $evento->setAnfitrion($item['anfitrion']);
            $evento->setTituloTrabajo($item['tituloTrabajo']);
            $fechaInicio = new DateTime($item['inicio']['date']);
            $evento->setFechaInicio($fechaInicio);
            $fechaFin = new DateTime($item['fin']['date']);
            $evento->setFechaFin($fechaFin);
            $informe->setImportsia(true);
            $evento->setInforme($informe);
            // Persist the entity
            $em->persist($evento);
            $count++;

        }

        // Flush all changes to the database
        $em->flush();

        //return new Response('Data imported successfully', Response::HTTP_OK);
        return $this->redirectToRoute('eventos_index', [
            'status' => 'success',
            'count' => $count,
            'visitantes'=>$visitantes,
            'comisiones'=>$comisiones,
            'licencias'=>$licencias,
        ]);

    }




}