<?php

// src/Controller/AdminController.php

namespace App\Controller;

use App\Entity\Informe;
use App\Entity\Plan;
use App\Entity\Tecnico;
use App\Repository\InformeRepository;
use App\Repository\AcademicoRepository;
use App\Repository\PlanRepository;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_index", methods={"GET"})
     */
    public function index(AcademicoRepository $academicoRepository, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $this->academicoRepository = $academicoRepository;
        $academicos = $academicoRepository->findAll();

        return $this->render('admin/index.html.twig', [
            'academicos' => $academicos,
        ]);
    }

    /**
     * @Route("/admin/nuevo/{anio}", name="admin_nuevo", methods={"GET"})
     */
    public function nuevo(AcademicoRepository $academicoRepository, ManagerRegistry $doctrine, $anio): Response
    {
        $entityManager = $doctrine->getManager();
        $this->academicoRepository = $academicoRepository;
        $academicos = $academicoRepository->findAll();

        foreach ($academicos as $academico) {

            // Query the repository to check if an Informe with the same year and academico already exists
            $existingInforme = $entityManager->getRepository(Informe::class)
                ->findOneBy([
                    'anio' => $anio,
                    'academico' => $academico
                ]);

            // Query the repository to check if a Plan with the same year and academico already exists
            $existingPlan = $entityManager->getRepository(Plan::class)
                ->findOneBy([
                    'anio' => $anio+1,
                    'academico' => $academico
                ]);

            // If no existing Informe is found, create and persist a new one
            if (!$existingInforme) {
                $informe = new Informe();
                $informe->setAnio($anio);
                $informe->setAcademico($academico);


                // Persist the new Informe record
                $entityManager->persist($informe);
            }

            // If no existing Plan is found, create and persist a new one
            if (!$existingPlan) {
                $plan = new Plan();
                $plan->setAnio($anio+1);
                $plan->setAcademico($academico);

                // Persist the new Plan record
                $entityManager->persist($plan);
            }

        }
        // Flush to execute the inserts
        $entityManager->flush();

        foreach ($academicos as $academico) {

            $user = $entityManager ->getRepository('App:Academico')->find($academico);
            $user = $user->getUser();
            $roles = $user->getRoles();
            $hasRoleTecnico = in_array('ROLE_TECNICO', $roles);


            if($hasRoleTecnico) {

                //   $informe=$entityManager->getRepository('App:Informe')->findOneByAnio($anio,$academico->getId());

                // Query the repository to check if an Informe with the same year and academico already exists
                $existingInforme = $entityManager->getRepository(Informe::class)
                    ->findOneBy([
                        'anio' => $anio,
                        'academico' => $academico
                    ]);

                $existingInformeTecnico = $entityManager->getRepository(Tecnico::class)
                    ->findOneByInforme($existingInforme);

                if (!$existingInformeTecnico) {
                    $tecnico = new Tecnico();
                    $tecnico->setInforme($existingInforme);
                    $entityManager->persist($tecnico);

                    // Persist the new Informe record
                    $entityManager->persist($existingInforme);
                }

            }
            // Flush to execute the inserts
            $entityManager->flush();


        }

        return $this->render('admin/index.html.twig', [
            'academicos' => $academicoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/admin/consulta/{academico}/{anio}", name="admin_consulta", methods={"GET"})
     */
    public function consulta($anio, $academico): Response
    {

        $em = $this->getDoctrine()->getManager();
        $user = $em ->getRepository('App:Academico')->find($academico);
        $user = $user->getUser();
        $roles = $user->getRoles();
        // Check if ROLE_TECNICO is in the roles array
        $hasRoleTecnico = in_array('ROLE_TECNICO', $roles);

        $informe = $em->getRepository('App:Informe')->findOneByAnio($anio,$academico);
        $plan = $em->getRepository('App:Plan')->findOneByAnio($anio+1,$academico);

        $visitas = $em->getRepository('App:Eventos')->findByVisitantes($informe->getId());

        if ($hasRoleTecnico) {

            $tecnicos = $em->getRepository('App:Tecnico')->findOneByInforme($informe);


            if (!$tecnicos) {
                // Handle the case where no result was found
                throw $this->createNotFoundException('No tecnico found for informe ' );
            }

            return $this->render('admin/consulta-tecnico.html.twig', [
                'informe' => $informe,
                'academico' => $academico,
                'plan' => $plan,
                'anio' => $anio,
                'visitas' => $visitas,
                'tecnicos' => $tecnicos,
            ]);


        } else {

            return $this->render('admin/consulta.html.twig', [
                'informe' => $informe,
                'academico' => $academico,
                'plan' => $plan,
                'anio' => $anio,
                'visitas' => $visitas,
            ]);
        }


        $informe = $em->getRepository('App:Informe')->findOneByAnio($anio,$academico);
        $plan = $em->getRepository('App:Plan')->findOneByAnio($anio+1,$academico);

        $visitas = $em->getRepository('App:Eventos')->findByVisitantes($informe->getId());

        return $this->render('admin/consulta.html.twig', [
            'informe' => $informe,
            'academico' => $academico,
            'plan' => $plan,
            'anio' => $anio,
            'visitas' => $visitas,

        ]);
    }




    /**
     * @Route("/reporte", name="reporte_index", methods={"GET"})
     */
    public function reporte(AcademicoRepository $academicoRepository, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $this->academicoRepository = $academicoRepository;
        $academicos = $academicoRepository->findAll();

        return $this->render('admin/reporte.html.twig', [
            'academicos' => $academicos,
        ]);
    }

    /**
     * @Route("/reporte/tesistas/{academico}/{anio}", name="reporte_tesistas", methods={"GET"})
     */
    public function reporteTesistas($anio, $academico): Response
    {
        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($anio,$academico);

        return $this->render('admin/reporte-tesistas.html.twig', [
            'informe' => $informe,
            'academico' => $academico,
            'anio' => $anio,

        ]);
    }

    /**
     * @Route("/reporte/cursos/{academico}/{anio}", name="reporte_cursos", methods={"GET"})
     */
    public function reporteCursos($anio, $academico): Response
    {
        $em = $this->getDoctrine()->getManager();
        $informe = $em->getRepository('App:Informe')->findOneByAnio($anio,$academico);

        return $this->render('admin/reporte-cursos.html.twig', [
            'informe' => $informe,
            'academico' => $academico,
            'anio' => $anio,

        ]);
    }


}
