<?php
namespace Stuff\Controller;
use Category\Entity\Category;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use Stuff\Form\StuffForm;
use Stuff\Entity\Stuff;
use Zend\Mvc\Controller\Plugin\FlashMessenger;
use Stuff\Filter\AddStuffFilter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
// Paginator
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;

// Container
use Zend\Session\Container;

/**
 * 
 */
class StuffController extends AbstractActionController {
	 /**
     * @var Doctrine\ORM\EntityManager
     */
	protected $em;
	
	public function getEntityManager(){
		if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        }
		return $this->em;
	}
	
	public function setEntityManager(EntityManager $em){
		$this->em = $em;
	}
	
	public function indexAction(){
	}
	
	public function addAction(){
		$user_id = (int) $this->params()->fromroute('user_id',0);
		
		if(!$user_id){
			return $this->redirect()->toRoute('user',array('action' => 'register'));
		}
		$form = new StuffForm();
		$filter = new AddStuffFilter();
		$form->setInputFilter($filter->getInputFilter());
		$request = $this->getRequest();
		
		if($request->isPost()){
			$form->setData($request->getPost());
			
			if($form->isValid()){
				$formdata = $form->getData();
                $stuff = new Stuff();
                $data = $stuff->getArrayCopy();
                $user = $this->getEntityManager()->find('User\Entity\User',$user_id);
                $category = $this->getEntityManager()->find('Category\Entity\Category',1);
                $data['stuff_name']    = $formdata['stuffname'];
                //$data['purpose']       = $formdata['purpose'];
                $data['description']   = $formdata['description'];
                $data['price']         = $formdata['price'];
                $data['category']      = $category;
                //$data['desired_stuff'] = $formdata['desiredstuff'];
                $data['user']          = $user;
                $data['state']         = 1;
                
                $stuff->populate($data);
				try{
					$this->getEntityManager()->persist($stuff);
					$this->getEntityManager()->flush();
					$this->flashMessenger()->addSuccessMessage("Add new stuff successfully");
					//return $this->redirect()->toRoute('stuff',array('user_id' => $user_id,
					//												'action' => 'index',
					//));
					$form = new StuffForm();
					return array('form' => $form);
				}
				catch(DBALException $e){
                    $this->flashMessenger()->addErrorMessage($e->getMessage());
				}
			}	
		}
		return array(
            'form' => $form,
        );
 	}
	
	public function deleteAction(){
            $user_id = (int) $this->params()->fromroute('user_id',0);

            if(!$user_id){
                    return $this->redirect()->toRoute('user',array('action' => 'register'));
            }
            $stuff_id = (int) $this->params()->fromroute('stuff_id',0);
            if (!$stuff_id) {
                return $this->redirect()->toRoute('stuff', array('action'=>'add'));
            } 

            $stuff = $this->getEntityManager()->find('Stuff\Entity\Stuff', $stuff_id);          
                    
            $request = $this->getRequest();
		
		if($request->isPost()){
                    if ($user_id != $stuff->__get($user_id)) 
                    {                        
                         $this->flashMessenger()->addErrorMessage("Invalid delete parameters.");
                         $return = array('error_messages' => $this->flashMessenger()->getCurrentErrorMessages());
                         return $return;
                    }
                    $data = $stuff->getArrayCopy();
                    $data['state'] = -1;
                    $stuff->populate($data);
                    $this->getEntityManager()->flush();
                    $this->flashMessenger()->addSuccessMessage("Delete stuff successfully.");                    
                    }$return = array(           
            'success_messages' => $this->flashMessenger()->getCurrentSuccessMessages(),
            'error_messages' => $this->flashMessenger()->getCurrentErrorMessages(),
        );
        
        $this->flashMessenger()->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_SUCCESS);
        $this->flashMessenger()->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_ERROR);
        return $return;
	}
	
	public function editAction(){

		$form = new AddStuffForm();
		$form->get('submit')->setAttribute('value', 'Edit');
		$user_id = (int) $this->params()->fromroute('user_id',0);
		
		if(!$user_id){
			return $this->redirect()->toRoute('user',array('action' => 'register'));
		}

		$stuff_id = (int) $this->params()->fromroute('stuff_id',0);
        if (!$stuff_id) {
            return $this->redirect()->toRoute('stuff', array('action'=>'add'));
        } 

        $stuff = $this->getEntityManager()->find('Stuff\Entity\Stuff', $stuff_id);

        $request = $this->getRequest();
		
		if($request->isPost()){
			
			$form->setData($request->getPost());
			
			if($form->isValid()){
				$formdata = $form->getData();
				$data = $stuff->getArrayCopy();
				$data['stuff_name'] = $formdata['stuffname'];
				$data['description'] = $formdata['description'];
				$data['price'] = $formdata['price'];
				$data['cat_id']= 1;
				$data['user_id'] = $user_id;
				$data['state'] = 0;
				
				$stuff->populate($data);
				try{
					$this->getEntityManager()->persist($stuff);
					$this->getEntityManager()->flush();
					// $this->flashMessenger()->addSuccessMessage("Your stuff has been edited successfully");
					return $this->redirect()->toRoute('stuff',array('user_id' => $user_id,
																	'action' => 'index',
					));
				}
				catch(DBALException $e){
					switch ($e->getPrevious()->getCode()) {
                        default:
                            $this->flashMessenger()->addErrorMessage($e->getMessage());
                        break;
					}
				}
			}
			else {
				foreach ($form->getMessages() as $message_array) {
                    foreach ($message_array as $message) {
                        $this->flashMessenger()->addErrorMessage($message);
                    }
                }
			}
				
		}$return = array(
            'form' => $form,
            'success_messages' => $this->flashMessenger()->getCurrentSuccessMessages(),
            'error_messages' => $this->flashMessenger()->getCurrentErrorMessages(),
        );
        
        $this->flashMessenger()->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_SUCCESS);
        $this->flashMessenger()->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_ERROR);
        
        return $return;
	}
    
    public function homeAction()
    {
        // Get filter request
        $filter_category    = $this->_getStateFromPostRequest('filter.category', 'filter_category');
        $filter_purpose     = $this->_getStateFromPostRequest('filter.purpose', 'filter_purpose');
        $filter_search      = $this->_getStateFromPostRequest('filter.search', 'filter_search');

        // Get stuffs
        $repository = $this->getEntityManager()->getRepository('Stuff\Entity\Stuff');
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('s')
                     ->from('Stuff\Entity\Stuff', 's')
                     ->orderBy('s.stuff_id', 'DESC');
        
        // Build the filter expression
        $and = $queryBuilder->expr()->andX();
        $parameters = new ArrayCollection();
        if ($filter_category) {
            $and->add($queryBuilder->expr()->eq('s.category', ':cat_id'));
            $parameters->add(new Parameter('cat_id', $filter_category, 'integer'));
        }
        if ($filter_purpose) {
            $and->add($queryBuilder->expr()->eq('s.purpose', ':purpose'));
            $parameters->add(new Parameter('purpose', $filter_purpose, 'string'));
        }
        if ($filter_search) {
            $and->add($queryBuilder->expr()->like('s.stuff_name', ':search'));
            $parameters->add(new Parameter('search', '%'.$filter_search.'%', 'string'));
        }
        $parts = $and->getParts();
        if (!empty($parts))
            $queryBuilder->where($and)->setParameters($parameters);
        
        // Create a paginator
        $paginator = new Paginator(new PaginatorAdapter(new ORMPaginator($queryBuilder)));
        $paginator->setItemCountPerPage(11);
        $page = (int) $this->params()->fromQuery('page');
        if ($page) 
            $paginator->setCurrentPageNumber($page);
        
        // Get categories
        $categories = $this->getEntityManager()->getRepository('Category\Entity\Category')->findBy(array(), array('cat_name' => 'ASC'));
        return array(
            'categories' => $categories,
            'paginator' => $paginator,
        );
    }
    
    /**
     * Get a value from post request or session if request not available
     * Auto save value into session when the value has been changed
     *
     * @param string $key       Key to get the value from session
     * @param string $parameter The parameter name of the value to get from POST request
     * @param mixed  $default   Default value if value is not available
     *
     * @return mixed|null
     */
    private function _getStateFromPostRequest($key, $parameter, $default = null)
    {
        $request = $this->getRequest()->getPost();
        $value = $request->get($parameter, $default);
        
        // Exchange request value with session value
        $session = new Container('stuff');
        if ($value !== null) {
            $session->offsetSet($key, $value);
        } else {
            $value = $session->offsetGet($key);
        }
        
        return $value;
    }
}
