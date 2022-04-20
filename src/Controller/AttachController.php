<?php

namespace App\Controller;

use App\Entity\Attach;
use App\Service\DataFormat;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class AttachController extends AbstractController
{
    #[Route('/admin/register/attach', name: 'admin_register_attach')]
    public function adminRegisterAttach(ManagerRegistry $doctrine, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $taxe = new Attach();
            $taxe->setPlantaoId($request->get('id'));
            $taxe->setName($request->get('name'));
            $taxe->setFile($request->get('file'));
            $taxe->setCreatedAt(new \DateTimeImmutable());
            $taxe->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($taxe);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Anexo cadastrado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/get/attachs/{id}', name: 'admin_get_attachs')]
    public function getAdminAttachs(ManagerRegistry $doctrine, SerializerInterface $serializer, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $attach = $doctrine->getRepository(Attach::class)->findBy([
            'plantao_id' => $id
        ]);

        $serialized = $serializer->serialize($attach,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/admin/delete/attach', name: 'admin_delete_attach')]
    public function adminDeleteAttach(ManagerRegistry $doctrine, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $attach = $em->getRepository(Attach::class)->find($request->get('id'));
            $em->remove($attach);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Anexo deletado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }
}
