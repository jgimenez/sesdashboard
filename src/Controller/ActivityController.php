<?php

namespace App\Controller;

use App\Repository\EmailRepository;
use App\Repository\ProjectRepository;
use App\Utils\ActivitySearchFilters;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ActivityController extends AbstractController
{
    /**
     * @Route("/activity", name="app_activity")
     */
    public function index(ProjectRepository $projectRepository)
    {

        $project = $projectRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        return $this->render('activity/index.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route("/activity/list/api", name="app_activity_list_api")
     */
    public function listApi(Request $request,
                            EmailRepository $emailRepository,
                            ProjectRepository $projectRepository,
                            PaginatorInterface $paginator,
                            SerializerInterface $serializer,
                            ActivitySearchFilters $activitySearchFilters)
    {
        $project = $projectRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$project) {
            return $this->json([
                'error' => 'No project found',
            ]);
        }

        try {
            $filters = $activitySearchFilters->getFiltersFromRequest($request);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ]);
        }

        $pagination = $paginator->paginate(
            $emailRepository->findByProjectQuery($project, $filters),
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10)
        );

        $rows = $pagination->getItems();

        return $this->json([
            'rows' => $serializer->normalize($rows, 'array', ['groups' => 'main']),
            'totalRows' => $pagination->getTotalItemCount(),
        ]);
    }

    /**
     * @Route("/activity/details/api", name="app_activity_details_api")
     */
    public function detailsApi(Request $request, EmailRepository $emailRepository, SerializerInterface $serializer)
    {
        // TODO Permissions
        $email = $emailRepository->find($request->get('id'));
        return $this->json($serializer->normalize($email, 'array', ['groups' => 'full']));
    }
}
