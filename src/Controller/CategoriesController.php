<?php

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\CategoriesModel;
use Model\PostsModel;

class CategoriesController implements ControllerProviderInterface
{
    protected $_model;
    protected $_posts;

    public function connect(Application $app)
    {
        $this->_model = new CategoriesModel($app);
        $this->_posts = new PostsModel($app);
        $categoryController = $app['controllers_factory'];
        $categoryController->get('/', array($this, 'index'))->bind('/categories/');
        $categoryController->match('/add/', array($this, 'add'))
            ->bind('/categories/add');
        $categoryController->match('/edit/{idcategory}', array($this, 'edit'))
            ->bind('/categories/edit');
        $categoryController
            ->match('/delete/{idcategory}', array($this, 'delete'))
            ->bind('/categories/delete');
        $categoryController->get('/all/', array($this,'all'))->bind('/categories/all');
        return $categoryController;
    }

    public function index(Application $app, Request $request)
    {
        $id = (int)$request->get('idcategory', 0);

        $check = $this->_model->checkCategoryId($id);

        if ($check) {
            $post = $this->_model->getPostsListByIdcategory($id);
            $category = $this->_model->getCategory($id);
            return $app['twig']
                ->render('categories/index.twig', array('posts' => $post, 'category' => $category,));
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono kategorii'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('/posts/'), 301
            );
        }
    }

    public function all(Application $app, Request $request)
    {

    $posts = $this -> _posts->getPostList();
    $categories = $this -> _model->getCategories();
        return $app['twig']->render(
            'categories/all.twig', array(
                'categories' => $categories,
                'posts' => $posts,
                //'paginator' => $paginator
            )
        );
    }


    public function add(Application $app, Request $request)
    {
        $data = array();

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'name', 'text', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 3,
                                'max' => 45,
                                'minMessage' => 'Minimalna ilość znaków to 3',
                                'maxMessage' =>
                                    'Maksymalna ilość znaków to {{ limit }}',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Nazwa nie jest poprawna',
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
                $model = $this->_model->addCategory($data);
                $app['session']->getFlashBag()
                    ->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Kategoria została dodana'
                        )
                    );
                return $app->redirect(
                    $app['url_generator']
                        ->generate('/categories'), 301
                );
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło niezgodnie z planem';
            }
        }

        return $app['twig']
            ->render(
                'categories/add.twig', array('form' => $form->createView())
            );
    }



}