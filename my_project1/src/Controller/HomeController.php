<?php

namespace App\Controller;

use App\Entity\Admin\Messages;
use App\Entity\Hotel;
use App\Form\Admin\MessagesType;
use App\Repository\Admin\CommentRepository;
use App\Repository\Admin\RoomRepository;
use App\Repository\HotelRepository;
use App\Repository\ImageRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Mailer\Bridge\Google\Smtp\GmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(SettingRepository $settingRepository,HotelRepository $hotelRepository)
    {
        $setting=$settingRepository->findAll();
        $slider=$hotelRepository->findBy(['status'=>'True'],['title'=>'ASC'] ,3);
        $hotels=$hotelRepository->findBy(['status'=>'True'],['title'=>'DESC'] ,4);
        $newhotels=$hotelRepository->findBy(['status'=>'True'],['title'=>'DESC'] ,6);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'setting' => $setting,
            'slider' => $slider,
            'hotels' => $hotels,
            'newhotels' => $newhotels,
        ]);
    }
    /**
     * @Route("/hotel/{id}", name="hotel_show", methods={"GET"})
     */
    public function show(Hotel $hotel,$id,ImageRepository $imageRepository,CommentRepository $commentRepository,RoomRepository $roomRepository): Response
    {
        $rooms =$roomRepository->findBy(['hotelid'=>$id, 'status'=>'True']);
        $comments=$commentRepository->findBy(['hotelid'=>$id, 'status'=>'True']);
        $images=$imageRepository->findBy(['hotel'=>$id]);

        return $this->render('home/hotelshow.html.twig', [
            'hotel' => $hotel,
            'images' => $images,
            'comments' => $comments,
            'rooms' => $rooms,


        ]);
    }
    /**
     * @Route("/about", name="hotel_about", methods={"GET"})
     */
    public function about(SettingRepository $settingRepository): Response
    {
        $setting=$settingRepository->findAll();
        return $this->render('home/aboutus.html.twig', [
            'setting' => $setting,
        ]);
    }
    /**
     * @Route("/contact", name="home_contact", methods={"GET","POST"})
     */
    public function contact(SettingRepository $settingRepository,Request $request): Response
    {
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);
        $submittedToken = $request->request->get('token');
        //dump($request);
        // die();

        $setting=$settingRepository->findAll();

        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('form-messeage', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();
                $message->setStatus('New');
                $message->setIp($_SERVER['REMOTE_ADDR']);
                $entityManager->persist($message);
                $entityManager->flush();
                $this->addFlash('success', 'Your message has been successfuly');

                //send email//

                $email = (new Email())
                    ->from($setting[0]->getSmtpemail())
                    ->to($form['email']->getData())
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('AllHoliday Your Request')
                    //->text('Sending emails is fun again!')
                    ->html("Dear ".$form['name']->getData() ."<br>
                           <p>We will evalute your request and contact you as soon as possible</p>
                           Thank You <br>
                           
                           <br>".$setting[0]->getCompany()." <br>
                           Address : ".$setting[0]->getAddress()."<br>
                           Phone   : ".$setting[0]->getPhone()."<br>"
                    );

                $transport = new GmailSmtpTransport($setting[0]->getSmtpemail(), $setting[0]->getSmtppassword());
                $mailer = new Mailer($transport);
                $mailer->send($email);


                return $this->redirectToRoute('home_contact');
            }
        }

        $setting=$settingRepository->findAll();
        return $this->render('home/contact.html.twig', [
            'setting' => $setting,
            'form' => $form->createView(),
        ]);
    }

}
