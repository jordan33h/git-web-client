<?php
/*
 * This file is part of the GitControlBundle package.
 *
 * (c) Paul Schweppe <paulschweppe@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VersionControl\GitControlBundle\Controller;

use VersionControl\GitControlBundle\Controller\Base\BaseProjectController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use VersionControl\GitControlBundle\Entity\Project;
use Symfony\Component\Validator\Constraints\NotBlank;
use VersionControl\GitCommandBundle\GitCommands\GitCommand;
use Symfony\Component\HttpFoundation\Request;
use VersionControl\GitControlBundle\Annotation\ProjectAccess;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Project controller.
 *
 * @Route("/project/{id}/sync/")
 */
class ProjectSyncController extends BaseProjectController
{
    /**
     * @var GitCommand
     */
    protected $gitCommands;

    /**
     * @var GitCommand
     */
    protected $gitSyncCommands;

    /**
     * Finds and displays a Project entity.
     *
     * @Route("push/", name="project_push")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="MASTER")
     */
    public function pushAction()
    {

        //Remote Server choice
        $gitRemoteVersions = $this->gitSyncCommands->getRemoteVersions();

        $pushForm = $this->createPushPullForm($this->project);
        $pushForm->add('push', SubmitType::class, array('label' => 'Push'));

        return array_merge($this->viewVariables, array(
            'remoteVersions' => $gitRemoteVersions,
            'push_form' => $pushForm->createView(),
            ));
    }

    /**
     * Finds and displays a Project entity.
     *
     * @Route("pushremote/", name="project_pushremote")
     * @Method("POST")
     * @Template("VersionControlGitControlBundle:ProjectSync:push.html.twig")
     * @ProjectAccess(grantType="MASTER")
     */
    public function pushToRemoteAction(Request $request, $id)
    {
        $gitRemoteVersions = $this->gitSyncCommands->getRemoteVersions();

        $pushForm = $this->createPushPullForm($this->project);
        $pushForm->add('push', SubmitType::class, array('label' => 'Push'));
        $pushForm->handleRequest($request);

        if ($pushForm->isValid()) {
            $data = $pushForm->getData();
            $remote = $data['remote'];
            $branch = $data['branch'];
            try {
                $response = $this->gitSyncCommands->push($remote, $branch);

                $this->get('session')->getFlashBag()->add('notice', $response);
                $this->get('session')->getFlashBag()->add('status-refresh', 'true');
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->redirect($this->generateUrl('project_push', array('id' => $id)));
        }

        return array_merge($this->viewVariables, array(
            'remoteVersions' => $gitRemoteVersions,
            'push_form' => $pushForm->createView(),
            ));
    }

    /**
     * Form to choose which brabch and remote a user will pull.
     * This is just the form. Also see pullToLocal().
     *
     * @Route("pull/", name="project_pull")
     * @Method("GET")
     * @Template()
     * @ProjectAccess(grantType="MASTER")
     */
    public function pullAction()
    {

        //Remote Server choice
        $gitRemoteVersions = $this->gitSyncCommands->getRemoteVersions();

        $pullForm = $this->createPushPullForm($this->project, 'project_pulllocal');
        $pullForm->add('pull', SubmitType::class, array('label' => 'Pull'));
        $pullForm->add('viewDiff', SubmitType::class, array('label' => 'View Diff'));

        return array_merge($this->viewVariables, array(
            'remoteVersions' => $gitRemoteVersions,
            'pull_form' => $pullForm->createView(),
            'diffs' => array(),
            ));
    }

    /**
     * Pulls git repository from remote to local.
     *
     * @Route("pulllocal/", name="project_pulllocal")
     * @Method("POST")
     * @Template("VersionControlGitControlBundle:ProjectSync:pull.html.twig")
     * @ProjectAccess(grantType="MASTER")
     */
    public function pullToLocalAction(Request $request, $id)
    {
        $diffs = array();

        $gitRemoteVersions = $this->gitSyncCommands->getRemoteVersions();

        $pullForm = $this->createPushPullForm($this->project, 'project_pulllocal');
        $pullForm->add('pull', SubmitType::class, array('label' => 'Pull'));
        $pullForm->add('viewDiff', SubmitType::class, array('label' => 'View Diff'));
        $pullForm->handleRequest($request);

        if ($pullForm->isValid()) {
            $data = $pullForm->getData();
            $remote = $data['remote'];
            $branch = $data['branch'];
             //die('form valid');
            if ($pullForm->get('viewDiff')->isClicked()) {
                $response = $this->gitSyncCommands->fetch($remote, $branch);
                $this->get('session')->getFlashBag()->add('notice', $response);
                $diffs = $this->gitCommands->getDiffRemoteBranch($remote, $branch);
            } else {
                try {
                    $response = $this->gitSyncCommands->pull($remote, $branch);
                    $this->get('session')->getFlashBag()->add('notice', $response);
                    $this->get('session')->getFlashBag()->add('status-refresh', 'true');
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('project_pull', array('id' => $id)));
            }
        }

        return array_merge($this->viewVariables, array(
            'remoteVersions' => $gitRemoteVersions,
            'pull_form' => $pullForm->createView(),
            'diffs' => $diffs,
            ));
    }

    public function initAction($id, $grantType = 'VIEW')
    {
        $redirectUrl = parent::initAction($id, $grantType);
        if ($redirectUrl) {
            return $redirectUrl;
        }
        $this->gitSyncCommands = $this->gitCommands->command('sync');
    }

    /**
     * Creates a form to edit a Project entity.
     *
     * @param Project $project The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createPushPullForm($project, $formAction = 'project_pushremote')
    {
        //Remote Server choice
        $gitRemoteVersions = $this->gitSyncCommands->getRemoteVersions();
        $remoteChoices = array();
        foreach ($gitRemoteVersions as $remoteVersion) {
            $remoteChoices[$remoteVersion[0].'('.$remoteVersion[1].')'] = $remoteVersion[0];
        }

        //Local Branch choice
        $branches = $this->gitCommands->command('branch')->getBranches(true);
        $branchChoices = array();
        foreach ($branches as $branchName) {
            $branchChoices[$branchName] = $branchName;
        }

        //Current branch
        $currentBranch = $this->gitCommands->command('branch')->getCurrentBranch();

        $firstOrigin = reset($remoteChoices);

        $defaultData = array('branch' => $currentBranch);
        $form = $this->createFormBuilder($defaultData, array(
                'action' => $this->generateUrl($formAction, array('id' => $project->getId())),
                'method' => 'POST',
            ))
            ->add('remote', ChoiceType::class, array(
                'label' => 'Remote Server', 'choices' => $remoteChoices, 'data' => $firstOrigin, 'required' => false, 'choices_as_values' => true, 'constraints' => array(
                    new NotBlank(),
                ), )
            )
            ->add('branch', ChoiceType::class, array(
                'label' => 'Branch', 'choices' => $branchChoices, 'preferred_choices' => array($currentBranch), 'data' => trim($currentBranch), 'required' => false, 'choices_as_values' => true, 'constraints' => array(
                    new NotBlank(),
                ), )
            )
            ->getForm();

        //$form->add('submitMain', SubmitType::class, array('label' => 'Push'));
        return $form;
    }
}
