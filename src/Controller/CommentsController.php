<?php


namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\CommentsModel;
use Model\UserModel;
use Model\PostsModel;


class CommentsController implements ControllerProviderInterface
{

    protected $_model;


    protected $_user;


    protected $_posts;


    public function connect(Application $app)
    {
        $this->_model = new CommentsModel($app);
        $this->_user = new UserModel($app);
        $this->_posts = new PostsModel($app);
        $commentController = $app['controllers_factory'];
        $commentController->get('/{page}/{idpost}/', array($this, 'index'))
            ->value('page', 1)
            ->bind('/comments/');
        $commentController
            ->match('/add/{idpost}', array($this, 'add'))
            ->bind('/comments/add');
        $commentController->match('/edit/{id}', array($this, 'edit'))
            ->bind('/comments/edit');
        $commentController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/comments/delete');
        return $commentController;
    }


    public function index(Application $app, Request $request)
    {
        $id = (int)$request->get('idpost', 0);

        $check = $this->_posts->checkPostId($id);

        if ($check) {

            $comments = $this->_model->getCommentsList($id);

           // $_isLogged = $this->_user->_isLoggedIn($app);
           // if ($_isLogged) {
             //   $access = $this->_user->getIdCurrentUser($app);
           // } else {
            //   $access = 1;
           // }

            return $app['twig']->render(
                'comments/index.twig', array(
                    'comments' => $comments, 'idpost' => $id,
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }


    public function add(Application $app, Request $request)
    {

        $idpost = (int)$request->get('idpost', 0);


        $check = $this->_posts->checkPostId($idpost);



        if ($check) {

         //   if ($this->_user->_isLoggedIn($app)) {
           //     $iduser = $this->_user->getIdCurrentUser($app);
           // } else {
          //   $iduser = 1;
            //}
            $data = array(
                'published' => date('Y-m-d'),
                'idpost' => $idpost,



            );


            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'content', 'textarea', array('required' => false,
                    'attr' => array(
                        'cols' => '80',
                        'rows' => '8')), array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Minimalna ilość znaków to 5',
                                )
                            ),
                            new Assert\Type(
                                array(
                                    'type' => 'string',
                                    'message' => 'tekst nie jest poprawny',
                                )
                            )
                        )
                    )
                )
                ->add(
                    'author', 'text', array('required' => true), array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Minimalna ilość znaków to 3',
                                )
                            ),
                            new Assert\Type(
                                array(
                                    'type' => 'string',
                                    'message' => 'tekst nie jest poprawny',
                                )
                            )
                        )
                    )
                )
                ->getForm();


            $form->handleRequest($request);


            if ($form->isValid()) {
                $data = $form->getData();
                try {
                    $model = $this->_model->addComment($data);

                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Komentarz został dodany'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/posts/'
                        ), 301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło niezgodnie z planem';
                }
            }
            return $app['twig']->render(
                'comments/add.twig', array(
                    'form' => $form->createView(),
                    'idpost' => $idpost
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }


    public function edit(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkCommentId($id);

        if ($check) {

            $idCurrentUser = $this->_user->getIdCurrentUser($app);
            $comment = $this->_model->getComment($id);

            if (count($comment)) {

                $data = array(
                    'idcomment' => $id,
                    'published_date' => date('Y-m-d'),
                    'idpost' => $comment['idpost'],
                    'iduser' => $comment['iduser'],
                    'idCurrentUser' => $idCurrentUser,
                    'content' => $comment['content'],
                );

                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'content', 'textarea', array(
                        'required' => false
                    ), array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(
                                    array(
                                        'min' => 5,
                                        'minMessage' =>
                                            'Minimalna ilość znaków to 5',
                                    )
                                ),
                                new Assert\Type(
                                    array(
                                        'type' => 'string',
                                        'message' => 'Tekst nie poprawny.',
                                    )
                                )
                            )
                        )
                    )
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    try {
                        $model = $this->_model->editComment($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Komanetarz został zmieniony'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/posts/'
                            ), 301
                        );
                    } catch (Exception $e) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
                }
                return $app['twig']->render(
                    'comments/edit.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono komentarza'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/comments/add'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );

        }
    }


    public function delete(Application $app, Request $request)
    {
        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkCommentId($id);

        if ($check) {

            $comment = $this->_model->getComment($id);

            $data = array();

            if (count($comment)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'idcomment', 'hidden', array(
                            'data' => $id,
                        )
                    )
                    ->add('Yes', 'submit')
                    ->add('No', 'submit')
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    if ($form->get('Yes')->isClicked()) {
                        $data = $form->getData();
                        try {
                            $model = $this->_model->deleteComment($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Komantarz został usunięty'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/posts/'
                                ), 301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Coś poszło niezgodnie z planem';
                        }
                    } else {
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/posts/'
                            ), 301
                        );
                    }
                }
                return $app['twig']->render(
                    'comments/delete.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono komentarza'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/posts/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );

        }
    }
}