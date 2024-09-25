<?php


namespace App\Controller;

use App\Entity\Productividad;
use App\Entity\Academico;
use App\Entity\User;
use App\Form\ProductividadType;
use App\Repository\AcademicoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\HttpClient;
use Doctrine\ORM\EntityManagerInterface;




use Symfony\Component\Security\Core\Exception\AccessDeniedException;
//use App\Security\InvesVoter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


/**
 * Productividad controller.
 *
 * @Route("/productividad")
 */

class ProductividadController extends AbstractController

{
    /**
     * Lists all Productividad entities.
     * @Route("/anual/{actual}", name="productividad_index", methods={"GET"}, defaults={"actual"="2024"})

     */
    public function indexAction($actual)
    {
        $em = $this->getDoctrine()->getManager();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $academico = $user->getAcademico();
        $apellido = $academico->getApellido();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);
        $productividades = $informe->getProductividades();
        $enviado = $informe->isEnviado();


        return $this->render('productividad/index.html.twig', array(
            'productividades' => $productividades,
            'enviado'=>$enviado,
            'anual'=>$actual,
            'apellido'=>$apellido,
            'informe'=>$informe,
        ));
    }

    /**
     * Creates a new Productividad entity.
     *
     * @Route("/new", name="productividad_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {

        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $twigglobals = $this->get("twig")->getGlobals();
        $actual = $twigglobals["actual"];

        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);

        $productividad = new Productividad();
        $form = $this->createForm('App\Form\ProductividadType', $productividad, array('user'=>$user));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $productividad->setInforme($informe);
            $em->persist($productividad);
            $em->flush();

            return $this->redirectToRoute('productividad_index');
            // return $this->redirectToRoute('dashboard');
        }

        return $this->render('productividad/new.html.twig', array(
            'productividad' => $productividad,
            'form' => $form->createView(),
        ));

    }

    /**
     * Finds and displays a Productividad entity.
     * @Route("/{id}", name="productividad_show", methods={"GET"})
     */
    public function show(Productividad $productividad): Response
    {

        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $twigglobals = $this->get("twig")->getGlobals();
        $actual = $twigglobals["actual"];

        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);
        $enviado = $informe->isEnviado();

        $deleteForm = $this->createDeleteForm($productividad);

        // check for "view" access: calls all voters
//        $this->denyAccessUnlessGranted('view', $productividad);

        return $this->render('productividad/show.html.twig', array(
            'productividad' => $productividad,
            'enviado'=>$enviado,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Productividad entity.
     *
     * @Route("/{id}/edit", name="productividad_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Productividad $productividad): Response
    {
        $em = $this->getDoctrine()->getManager();

        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
 //       $academico = $user->getAcademico();


//        $informe = $em->getRepository('App:Informe')->findOneByAnio(2021, $academico);
        $deleteForm = $this->createDeleteForm($productividad);
//        $this->denyAccessUnlessGranted('edit', $productividad);
        $editForm = $this->createForm('App\Form\ProductividadType', $productividad, array('user'=>$user));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($productividad);
            $em->flush();

            //return $this->redirectToRoute('investigacion_show', array('id' => $investigacion->getId()));

            return $this->redirectToRoute('productividad_index');

        }

        return $this->render('productividad/edit.html.twig', array(
            'id'=>$productividad->getId(),
            'productividad' => $productividad,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Productividad entity.
     *
     * @Route("/{id}", name="productividad_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Productividad $productividad)
    {
        $form = $this->createDeleteForm($productividad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($productividad);
            $em->flush();
        }

        return $this->redirectToRoute('productividad_index');
    }

    /**
     * Creates a form to delete a Productividad entity.
     *
     * @param Productividad $productividad The Productividad entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Productividad $productividad)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('productividad_delete', array('id' => $productividad->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/import-srb/{author}/{year}", name="import_srb")
     */
    public function importData(EntityManagerInterface $em, $author, $year): Response
    {
        $securityContext = $this->container->get('security.token_storage');
        $user = $securityContext->getToken()->getUser();
        $academico = $user->getAcademico();
        $twigglobals = $this->get("twig")->getGlobals();
        $actual = $twigglobals["actual"];

        // Decode URL-encoded string
        $decodedName = urldecode($author);

        // Split the decoded string by space
        $nameParts = explode(' ', $decodedName);

        // Get the first part
        $author = $nameParts[0] ?? '';

        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($actual, $academico);

        $url = 'https://gaspacho.matmor.unam.mx/SRBexport/app_dev.php/export/'.$author.'/'.$year;

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

        foreach ($data as $item) {
            // Ensure the JSON structure matches your entity fields
            $referencia = new Productividad();
            $referencia->setTipo($item['tipo']);
            $referencia->setTitulo($item['titulo']);
            $referencia->setAutores($item['autores']);
            $referencia->setYear($item['year']);
            $referencia->setPags($item['pags']);
            $referencia->setVolumen($item['volumen']);
            $referencia->setNumero($item['numero']);
            $referencia->setIssn($item['issn']);
            $referencia->setRevista($item['revista']);
            $referencia->setStatus('Publicado');
            $referencia->setInforme($informe);
            $informe->setImportsrb(true);

            // Persist the entity
            $em->persist($referencia);
            $count++;

        }

        // Flush all changes to the database
        $em->flush();

        //return new Response('Data imported successfully', Response::HTTP_OK);
        return $this->redirectToRoute('productividad_index', ['status' => 'success', 'count' => $count]);

    }




}