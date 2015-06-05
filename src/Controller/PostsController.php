<?php

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\PostsModel;
use Model\CategoriesModel;
use Model\UserModel;
use Model\CommentsModel;

class PostsController implements ControllerProviderInterface
{
    protected $_model;

    protected $_category;

    protected $_user;

    public function connect(Application $app)
    {
        $this->_model = new PostsModel($app);
        $this->_category = new CategoriesModel($app);
        $this->_user = new UserModel($app);
        $postController = $app['controllers_factory'];
        $postController->get('/', array($this, 'index'))
            //->value('page', 1)
            ->bind('/posts/');
        $postController->match('/add/', array($this, 'add'))
            ->bind('/posts/add');
        $postController->match('/edit/{id}', array($this, 'edit'))
            ->bind('/posts/edit');
        $postController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/posts/delete');
        $postController->get('/view/{id}', array($this, 'view'))
            ->bind('/posts/view');
        return $postController;
    }


    public function index(Application $app, Request $request)
    {
        $pageLimit = 5;
        $page = (int)$request->get('page', 1);
        $pagesCount = $this->_model->countPostsPages($pageLimit);
        if (($page < 1) || ($page > $pagesCount)) {
            $page = 1;
        }
        $posts = $this->_model->getPostsPage($page, $pageLimit, $pagesCount);
        $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
        return $app['twig']->render(
            'posts/index.twig', array(
                'posts' => $posts,
                'paginator' => $paginator
            )
        );
    }


    public function add(Application $app, Request $request)
    {

        $categories = $this->_category->getCategoriesDict();

        $data = array(
            'published' => date('Y-m-d'),
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'title', 'text', array(
                    'label'=> 'Tytuł ',
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 3,
                                'max' => 45,
                                'minMessage' =>
                                    'Minimalna ilość znaków to 3',
                                'maxMessage' =>
                                    'Maksymalna ilość znaków to {{ limit }}',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Tytuł nie jest oprawny.',
                            )
                        )
                    )
                )
            )
            ->add(
                'content', 'textarea', array(
                'label' => 'Treść wpisu',
                'required' => false,
                'attr' => array(
                    'cols' => '100',
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
                                'message' => 'Tekst nie jest poprawny.',
                            )
                        )
                    )
                )
            )

            ->add(
                'category', 'choice', array(
                    'label' => 'kategoria',
                    'empty_value' => ' ',
                    'choices' => $categories,
                )
            )
            ->add('Dodaj przepis', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            try {
                $model = $this->_model->addPost($data);

                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 'Post został dodany'
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
            'posts/add.twig', array(
                'form' => $form->createView()
            )
        );
    }

    public function edit(Application $app, Request $request)
    {
        $categories = $this->_category->getCategoriesDict();

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkPostId($id);

        if ($check) {

            $post = $this->_model->getPost($id);

            $data = array(
                'title' => $post['title'],
                'content' => $post['content'],
                'published' => $post['published'],
                'idcategory' => $post['idcategory'],
            );

            if (count($post)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'id', 'hidden', array(
                            'data' => $id,
                        )
                    )
                    ->add(
                        'title', 'text', array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(
                                    array(
                                        'min' => 3,
                                        'max' => 45,
                                        'minMessage' =>
                                            'Minimalna ilość znaków to 3',
                                        'maxMessage' =>
                                            'Maksymalna ilość znaków to 45',
                                    )
                                ),
                                new Assert\Type(
                                    array(
                                        'type' => 'string',
                                        'message' => 'Tekst nie jest poprawny',
                                    )
                                )
                            )
                        )
                    )
                    ->add(
                        'content', 'textarea', array(
                        'required' => false,
                        'attr' => array(
                            'cols' => '100',
                            'rows' => '8')
                    ), array(
                            'constraints' => array(new Assert\NotBlank(),
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
                                        'message' => 'Tekst nie jest poprawny',
                                    )
                                )
                            )
                        )
                    )

                    ->add(
                        'category', 'choice', array(
                            'choices' => $categories,
                        )
                    )
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    try {
                        $model = $this->_model->editPost($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Post został zmieniony'
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
                    'posts/edit.twig', array(
                        'form' => $form->createView(),
                        'idpost' => $id
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleniono postu'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/posts/add'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleniono postu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/add'
                ), 301
            );
        }
    }

    public function delete(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkPostId($id);

        if ($check) {

            $post = $this->_model->getPost($id);

            $data = array();

            if (count($post)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'idpost', 'hidden', array(
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
                            $model = $this->_model->deletePost($data['idpost']);

                            $app['session']->getFlashBag()
                                ->add(
                                    'message', array(
                                        'type' => 'success',
                                        'content' => 'Post został usunięty'
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
                    'posts/delete.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleniono postu'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleniono postu'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    public function view(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkPostId($id);

        if ($check) {
            $post = $this->_model->getPostWithCategoryName($id);


            if (count($post)) {
                return $app['twig']->render(
                    'posts/view.twig', array(
                        'post' => $post,
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleniono postu'
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
                    'content' => 'Nie znaleniono postu'
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