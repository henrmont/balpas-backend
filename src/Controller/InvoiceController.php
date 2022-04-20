<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Service\DataFormat;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class InvoiceController extends AbstractController
{
    #[Route('/admin/register/invoice', name: 'admin_register_invoice')]
    public function adminRegisterInvoice(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $invoice = new Invoice();
            $invoice->setUser($request->get('user'));
            $invoice->setValue(100);
            $invoice->setDueDate(new \DateTimeImmutable($request->get('dueDate')));
            $invoice->setPdf($request->get('pdf'));
            $invoice->setIsPaid(false);
            $invoice->setCreatedAt(new \DateTimeImmutable());
            $invoice->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($invoice);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Fatura cadastrada com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/get/invoices', name: 'admin_get_invoices')]
    public function getAdminInvoices(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $invoices = $doctrine->getRepository(Invoice::class)->findBy([
        ],[
            'id' => 'DESC'
        ]);

        $serialized = $serializer->serialize($invoices,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/get/invoice/{id}', name: 'get_invoice')]
    public function getInvoice(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $invoice = $doctrine->getRepository(Invoice::class)->find($id);

        $serialized = $serializer->serialize($invoice,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/admin/edit/invoice', name: 'admin_edit_invoice')]
    public function adminEditInvoice(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $invoice = $em->getRepository(Invoice::class)->find($request->get('id'));
            $invoice->setDueDate(new \DateTimeImmutable($request->get('dueDate')));
            $invoice->setPdf($request->get('pdf'));
            $invoice->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($invoice);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Fatura alterada com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/delete/invoice', name: 'admin_delete_invoice')]
    public function adminDeleteInvoice(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $invoice = $em->getRepository(Invoice::class)->find($request->get('id'));
            $em->remove($invoice);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Fatura deletada com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/status/invoice', name: 'admin_status_invoice')]
    public function adminStatusInvoice(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $invoice = $em->getRepository(Invoice::class)->find($request->get('id'));
            if ($invoice->getIsPaid()) {
                $invoice->setIsPaid(false);
            } else {
                $invoice->setIsPaid(true);
            }
            $em->persist($invoice);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Status da fatura alterado',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/get/latest/invoice', name: 'get_latest_invoice')]
    public function getLatestInvoice(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {

            $invoice = $doctrine->getRepository(Invoice::class)->findOneBy([
                'user' => $user->getEmail()
            ],[
                'due_date' => 'DESC'
            ]);

            $serialized = $serializer->serialize($invoice,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Email inválido',
                'status' => 0
            ]);
        }
    }
}
