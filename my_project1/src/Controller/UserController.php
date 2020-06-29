<?php

namespace App\Controller;

use App\Entity\Admin\Comment;
use App\Entity\Admin\Reservation;
use App\Entity\User;
use App\Form\Admin\CommentType;
use App\Form\Admin\ReservationType;
use App\Form\UserType;
use App\Repository\Admin\CommentRepository;
use App\Repository\Admin\ReservationRepository;
use App\Repository\Admin\RoomRepository;
use App\Repository\HotelRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('user/show.html.twig');
    }
    /**
     * @Route("/comments", name="user_comments", methods={"GET"})
     */
    public function  comments(CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();
        $comments = $commentRepository->getAllCommentUser($user->getId());
        return $this->render('user/comments.html.twig', [
            'comments'=> $comments,
        ]);
    }
    /**
     * @Route("/hotels", name="user_hotels", methods={"GET"})
     */
    public function  hotels(): Response
    {
        return $this->render('user/hotels.html.twig');
    }
    /**
     * @Route("/rezervations", name="user_rezervations", methods={"GET"})
     */
    public function  rezervations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $reservations= $reservationRepository->getUserReservation($user->getId());
        return $this->render('user/rezervations.html.twig', [
            'reservations' => $reservations,
        ]);
    }
    /**
     * @Route("/rezervation/{id}", name="user_rezervation_show", methods={"GET"})
     */
    public function  rezervationshow($id,ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $reservation= $reservationRepository->getReservation($id);

        return $this->render('user/rezervation_show.html.twig', [
            'reservation' => $reservation,
        ]);
    }
    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request,$id, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = $this->getUser();
        if($this->getId() != $id) {
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $file = $form['image']->getData();
            if ($file) {
                $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e){

                }
                $user->setImage($fileName);
            }

            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form['image']->getData();
            if ($file) {
                $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e){

                }
                $user->setImage($fileName);
            }
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }
    /**
     * @Route("/newcomment/{id}", name="user_new_comment", methods={"GET","POST"})
     */
    public function newcomment(Request $request,$id): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        $submittedToken = $request->request->get('token');

        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('comment', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $comment->setStatus('New');
                $comment->setIp($_SERVER['REMOTE_ADDR']);
                $comment->setHotelid($id);
                $user = $this->getUser();
                $comment->setUserid($user->getId());

                $entityManager->persist($comment);
                $entityManager->flush();

                $this->addFlash('success', 'Your message has been successfuly');

                return $this->redirectToRoute('hotel_show', ['id' => $id]);
            }
        }
        return $this->redirectToRoute('hotel_show', ['id' => $id]);
    }
    /**
     * @Route("/reservation/{hid}/{rid}", name="user_reservation_new", methods={"GET","POST"})
     */
    public function newreservation(Request $request,$hid,$rid,HotelRepository $hotelRepository,RoomRepository $roomRepository): Response
    {
        $hotel=$hotelRepository->findOneBy(['id'=>$hid]);
        $room=$roomRepository->findOneBy(['id'=>$rid]);

        $days=$_REQUEST["days"];
        $checkin=$_REQUEST["checkin"];
        $checkout=Date("Y-m-d H:i:s", strtotime($checkin ."$days Day"));
        $checkin=Date("Y-m-d H:i:s", strtotime($checkin ." 0 Day"));

        $data["total"]=$days * $room->getPrice();
        $data["days"]=$days;
        $data["checkin"]=$checkin;
        $data["checkout"]=$checkout;

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');
        if ($form->isSubmitted()) {
            if($this->isCsrfTokenValid('form-reservation', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $checkin=date_create_from_format("Y-m-d H:i:s",$checkin);
                $checkout=date_create_from_format("Y-m-d H:i:s",$checkout);

                $reservation->setCheckin($checkin);
                $reservation->setCheckout($checkout);
                $reservation->setStatus('New');
                $reservation->setIp($_SERVER['REMOTE_ADDR']);
                $reservation->setHotelid($hid);
                $reservation->setRoomid($rid);
                $user= $this->getUser();
                $reservation->setUserid($user->getId());
                $reservation->setDays($days);
                $reservation->setTotal($data["total"]);

                $entityManager->persist($reservation);
                $entityManager->flush();

                return $this->redirectToRoute('user_rezervations');
            }
        }

        return $this->render('user/newreservation.html.twig', [
            'reservation' => $reservation,
            'hotel' => $hotel,
            'data' => $data,
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }
}
