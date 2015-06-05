<?php
/**
 * User Controller
 *
 * PHP version 5
 *
 * @category Controller
 * @package  Controller
 * @author   Agnieszka Gorgolewska <agnieszka.gorgolewska@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     wierzba.wzks.uj.edu.pl/~12_gorgolewska
 */
namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UserModel;

/**
 * Class UserController
 *
 * @category Controller
 * @package  Controller
 * @author   Agnieszka Gorgolewska <agnieszka.gorgolewska@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     wierzba.wzks.uj.edu.pl/~12_gorgolewska
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\UserModel
 */
class UsersController implements ControllerProviderInterface
{
    /**
     * UserModel object
     *
     * @var $_model
     * @access protected
     */
    protected $_model;

    /**
     * Connection
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->_model = new UserModel($app);
        $userController = $app['controllers_factory'];
        $userController->get('/', array($this, 'index'))->bind('/user/');
        $userController->match('/add/', array($this, 'add'))->bind('/user/add');
        $userController->match('/edit/{id}', array($this, 'edit'))
            ->bind('/user/edit');
        $userController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/user/delete');
        $userController->get('/view/', array($this, 'view'))
            ->bind('/user/view');
        return $userController;
    }

    /**
     * Wyświetl listę wszystkich użytkowników
     *
     * @param Application $app      application object
     * @param Request     $request  request
     *
     * @access public
     * @return mixed Generates page
     */
    public function index(Application $app)
    {

        $userModel = new UserModel($app);
        $users = $userModel->getAllUsers();
        return $app['twig']->render(
            'users/index.twig', array(
                'users' => $users)
        );
    }

    /**
     * Dodaj nowego użytkownika
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function add(Application $app, Request $request)
    {

        $data = array();
        $form = $app['form.factory']->createBuilder('form', $data)
            //->add ('id', 'hidden')
            ->add(
                'login', 'text', array(
                    'invalid_message' =>
                        'Poprawny login zawiera wyłącznie'
                        . ' litery, cyfry i znaki . - _',
                    'label' => 'login',
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 3, 'max' => 30)
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/^[a-zA-Z0-9\.\-_]{5,30}/",
                                'message' => 'Twój login jest niepoprawny')
                        )
                    ))
            )
            ->add(
                'password', 'password', array(
                    'label' => 'hasło',
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 2)
                        )
                    )
                )
            )
            ->add(
                'email', 'email', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5
                            )
                        ),
                    )
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $sprawdz = $this->_model->getUserByLogin($data['login']);

            if (!$sprawdz) {

                $data['password'] = $app['security.encoder.digest']
                    ->encodePassword("{$data['password']}", '');

                try {
                    $model = $this->_model->addUser($data);



                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Konto zostało stworzone'
                        )
                    );


                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/auth/login'
                            ), 301
                        );

                } catch (Exception $ex) {
                    $errors[] = 'Nieoczekiwany błąd';
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Użytkownik o tym loginie już istnieje'
                    )
                );

                return $app['twig']->render(
                    'users/add.twig', array(
                        'form' => $form->createView()
                    )
                );
            }
        } else {

            return $app['twig']->render(
                'users/add.twig', array(
                    'form' => $form->createView()
                )
            );
        }

        return $app['twig']->render(
            'users/add.twig', array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Edytuj dane użytkownika
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function edit(Application $app, Request $request)
    {

        $id = (int) $request->get('id', 0);
        $sprawdz = $this->_model->sprawdzUser($id);

        if ($sprawdz) {
            $user = $this->_model->getUser($id);
//default values
            $data = array(
                'login' => $user['login'],
                'password' => $user['password'],
                'email' => $user['email'],
            );

            if (count($user)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'id', 'hidden', array(
                            'data' => $id,)
                    )
                    ->add(
                        'login', 'text', array(
                            'constraints' => array(
                                new Assert\NotBlank(), new Assert\Length(
                                    array(
                                        'min' => 3, 'max' => 30)
                                ), new Assert\Regex(
                                    array(
                                        'pattern' => "/^[a-zA-Z0-9\.\-_]{5,30}/",
                                        'message' => 'Twój login jest niepoprawny')
                                )
                            )
                        )
                    )
                    ->add(
                        'haslo', 'password', array(
                            'label' => 'hasło',
                            'constraints' => array(
                                new Assert\NotBlank(), new Assert\Length(
                                    array(
                                        'min' => 2)
                                )
                            )
                        )
                    )
                    ->add(
                        'email', 'email', array(
                            'constraints' => array(
                                new Assert\NotBlank(),
                                new Assert\Length(
                                    array(
                                        'min' => 5
                                    )
                                ),
                            )
                        )
                    )

                    ->getForm();
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $data = $form->getData();
                    $data['password'] = $app['security.encoder.digest']
                        ->encodePassword("{$data['password']}", '');

                    try {
                        $model = $this->_model->editUser($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Informacje zostały zmienione'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/users/view'
                            ), 301
                        );
                    } catch (Exception $ex) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
                }

                return $app['twig']->render(
                    'users/edit.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono użytkownika'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/users/view'
                    ), 301
                );
            }
        }
    }

    /**
     * Usuń użytkownika
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function delete(Application $app, Request $request)
    {
        $id = (int) $request->get('id', 0);
        $sprawdz = $this->_model->sprawdzUser($id);

        if ($sprawdz) {
            $user = $this->_model->getUser($id);
            $data = array();

            if (count($user)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add('iduser', 'hidden', array('data' => $id,))
                    ->add('Tak', 'submit')
                    ->add('Nie', 'submit')
                    ->getForm();
                $form->handleRequest($request);

                if ($form->isValid()) {

                    if ($form->get('Tak')->isClicked()) {
                        $data = $form->getData();
                        try {
                            $model = $this->_model->usunUser($data);
                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success', 'content' =>
                                        'Użytkownik usunięty')
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/user/'
                                ), 301
                            );
                        } catch (Exception $ex) {
                            $errors[] = 'Nastąpił nieoczekiwany błąd';
                        }
                    } else {
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/user/'
                            ), 301
                        );
                    }
                } else {
                    return $app['twig']->render(
                        'user/delete.twig', array(
                            'form' => $form->createView())
                    );
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger', 'content' =>
                            'Brak użytkownika w systemie')
                );
                $app['session']->getFlashBag()->set(
                    'error', 'Brak użytkownika'
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/user/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger', 'content' => 'Brak użytkownika')
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/user/'
                ), 301
            );
        }
    }

    /**
     * Wyświetla informacje o zalogowanym użytkowniku
     *
     * @param \Silex\Application $app
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function view(Application $app)
    {

        $id = $this->_model->getIdCurrentUser($app);
        $user = $this->_model->getUser($id);

        if (count($user)) {
            return $app['twig']->render(
                'user/view.twig', array(
                    'user' => $user
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono użytkownika'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/'
                ), 301
            );
        }
    }

}