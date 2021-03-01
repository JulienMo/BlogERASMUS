<?php

namespace App\Controller;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    private $om;

    /**
     * @Route("/blog", name="blog")
     */
    public function index()
    {
        $articles =$this->getDoctrine()->getRepository(Article::class)->findBy(
            ['isPublised' => true],
            ['publicationDate' => 'desc']
        );
        return $this->render('blog/index.html.twig', ['articles' => $articles]);
    }

    public function add(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $article->setLastUpdateDate(new \DateTime());

            if ($article->getPicture() !== null) {
                $file = $form->get('picture')->getData();
                $fileName = uniqid() . '.' . $file->guessExtension();  

                try {
                    $file->move(
                        $this->getParameter('images_directory'),    // Le dossier dans le quel le fichier va etre charger
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $article->setPicture($fileName);
            }

            if ($article->getIsPublised()) {
                $article->setPublicationDate(new \DateTime());
            }

            $em = $this->getDoctrine()->getManager();   // On récupère l'entity manager
            $em->persist($article);                     // On confie notre entité à l'entity manager (on persist l'entité)
            $em->flush();                               // On execute la requete

            return $this->redirectToRoute('admin');

        }

        return $this->render('blog/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function show(Article $article)
    {
    	return $this->render('blog/show.html.twig', [
            'article' => $article
        ]);
    }
    
    public function edit(Article $article, Request $request)
    {
        $oldPicture = $article->getPicture();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setLastUpdateDate(new \DateTime());

            if ($article->getIsPublised()) {
                $article->setPublicationDate(new \DateTime());
            }

            if ($article->getPicture() !== null && $article->getPicture() !== $oldPicture) {
                $file = $form->get('picture')->getData();
                $fileName = uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $article->setPicture($fileName);
            } else {
                $article->setPicture($oldPicture);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('admin');
    
        }

    	return $this->render('blog/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView()
        ]);
    }
    
    public function remove($id, EntityManagerInterface $om)
    {
        $this->om = $om;

        $articleRepository = $this->om->getRepository(Article::class);
        $articleDel = $articleRepository->findOneBy(array('id' => $id));
        
        $em = $this->getDoctrine()->getManager();
        $em->remove($articleDel);
        $em->flush();

        return $this->redirectToRoute('admin');
    }

    public function admin()
    {
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(
            [],
            ['lastUpdateDate' => 'DESC']
        );

        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return $this->render('admin/index.html.twig', [
            'articles' => $articles,
            'users' => $users
        ]);
    }

    /**
     * @Route("/aboutus", name="aboutus")
     */
    public function aboutus () 
    {
        return $this->render('autres/aboutus.html.twig');
    }

    /**
     * @Route("/rechercher", name="rechercher")
     */
    public function rechercher () 
    {
        return $this->render('autres/aboutus.html.twig');
    }
}
