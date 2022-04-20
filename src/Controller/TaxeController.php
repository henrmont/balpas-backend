<?php

namespace App\Controller;

use App\Entity\Taxe;
use App\Service\DataFormat;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class TaxeController extends AbstractController
{
    #[Route('/admin/register/taxe', name: 'admin_register_taxe')]
    public function adminRegisterTaxe(ManagerRegistry $doctrine, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $taxe = new Taxe();
            $taxe->setPlantaoId($request->get('id'));
            $taxe->setName($request->get('name'));
            $taxe->setType($request->get('type'));
            $taxe->setValue($request->get('value'));
            $taxe->setCreatedAt(new \DateTimeImmutable());
            $taxe->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($taxe);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Valor cadastrado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/get/taxes/{id}', name: 'admin_get_taxes')]
    public function getAdminTaxes(ManagerRegistry $doctrine, SerializerInterface $serializer, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $taxes = $doctrine->getRepository(Taxe::class)->findBy([
            'plantao_id' => $id
        ]);

        $serialized = $serializer->serialize($taxes,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/get/taxe/{id}', name: 'get_taxe')]
    public function getTaxe(ManagerRegistry $doctrine, SerializerInterface $serializer, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $taxe = $doctrine->getRepository(Taxe::class)->find($id);

        $serialized = $serializer->serialize($taxe,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/admin/edit/taxe', name: 'admin_edit_taxe')]
    public function adminEditTaxe(ManagerRegistry $doctrine, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $taxe = $em->getRepository(Taxe::class)->find($request->get('id'));
            $taxe->setName($request->get('name'));
            $taxe->setType($request->get('type'));
            $taxe->setValue($request->get('value'));
            $taxe->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($taxe);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Valor editado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/delete/taxe', name: 'admin_delete_taxe')]
    public function adminDeleteTaxe(ManagerRegistry $doctrine, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $taxe = $em->getRepository(Taxe::class)->find($request->get('id'));
            $em->remove($taxe);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Valor deletado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }
}
