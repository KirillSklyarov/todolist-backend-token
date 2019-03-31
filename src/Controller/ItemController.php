<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\User;
use App\Model\ApiResponse;
use App\Repository\ItemRepository;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1")
 */
class ItemController extends AbstractController
{
    /**
     * @param array $input
     * @return ConstraintViolationListInterface
     */
    public function validatePost(array $input): ConstraintViolationListInterface
    {
        $validator = Validation::createValidator();
        $collection = [
            'title' => [
                new Assert\Length([
                    'min' => 1,
                    'max' => 255,
                ])
            ],
            'date' => [
                new Assert\Date()
            ],
            'description' => [],
            'position' => [],
        ];

        if(\array_key_exists('description', $input) && $input['description']) {
            $collection['description'] = [
                new Assert\Length([
                    'max' => 4000,
                ])
            ];
        }

        if (\array_key_exists('position', $input)) {
            $collection['position'] = [
                new Assert\Optional(),
                new Assert\Type([
                    'type' => 'integer',
                ])
            ];
        }
        $constraint = new Assert\Collection($collection);
        $violations = $validator->validate($input, $constraint);

        return $violations;
    }

    public function validateGet(array $input)
    {
        $validator = Validation::createValidator();
        $collection = [
            'date' => [
                new Assert\Date([
                ])
            ]
        ];
        if (\array_key_exists('count', $input)) {
            $collection['count'] = [
                new Assert\LessThanOrEqual([
                    'value' => $this->getParameter('items.max.result')
                ])
            ];
        }
        $constraint = new Assert\Collection($collection);
        $violations = $validator->validate($input, $constraint);

        return $violations;
    }
    /**
     * @Route("/item", methods={"POST", "OPTIONS"}, name="item_create")
     * @param Request $request
     * @param ItemRepository $itemRepository
     * @return ApiResponse
     * @throws \Exception
     */
    public function create(Request $request, ItemRepository $itemRepository)
    {
        // TODO: вставка между существующими
        /** @var User $user */
        $user = $this->getUser();

        $input = [
            'title' =>$request->get('title'),
            'date' =>$request->get('date'),
            'description' =>$request->get('description'),
            'position' =>$request->get('position'),
        ];
        $errors = $this->validatePost($input);
        if (\count($errors) > 0) {
            throw new BadRequestHttpException($errors);
        }
        $date = new \DateTime($input['date'], new DateTimeZone('UTC'));
        $item = (new Item())
            ->setTitle($input['title'])
            ->setDescription(\array_key_exists('description', $input) ? $input['description'] : '')
            ->setDate($date);
        $user->addItem($item);
        $lastPosition = $itemRepository->getLastPosition($user, $item->getDate());
        $item->setPosition(null === $lastPosition ? 0 : $lastPosition + 1);
        $itemRepository->create($item);

        return new ApiResponse([
            'item' => $item->toArray(),
            'totalCount' => $itemRepository->getCount($user, $item->getDate())
        ], true, '', 201);
    }


    /**
     * @Route("/items/{inputDate}/{page}/{count}", methods={"GET", "OPTIONS"},
     *     name="item_read_items",
     *     requirements={"inputDate"="\d{4}-\d{2}-\d{2}", "count"="\d+", "page"="\d+"})
     * @param string $inputDate
     * @param int $page
     * @param int $count
     * @param ItemRepository $itemRepository
     * @return ApiResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function readItems(string $inputDate, int $page, int $count,
                              ItemRepository $itemRepository)
    {
        $errors = $this->validateGet([
            'date' => $inputDate,
            'count' => $count
        ]);
        if (\count($errors) > 0) {
            throw new BadRequestHttpException($errors);
        }
        $date = new \DateTime(
            $inputDate,
            new DateTimeZone('UTC')
        );
        $start = $page * $count - $count;
        /** @var User $user */
        $user = $this->getUser();
        $items = $itemRepository->findBy(['user' => $user, 'date' => $date],
            ['position' => 'ASC'], $count, $start);
        $result = [];
        foreach ($items as $item) {
            $result[] = $item->toArray(true);
        }
        return new ApiResponse([
            'items' => $result,
            'totalCount' => $itemRepository->getCount($user, $date)
        ]);
    }

    /**
     * @Route("/item/{uuid}", methods={"DELETE", "OPTIONS"}, name="item_delete",
     *     requirements={"uuid" = "^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$"})
     * @param string $uuid
     * @param ItemRepository $itemRepository
     * @return ApiResponse
     * @throws \Exception
     */
    public function delete(string $uuid, ItemRepository $itemRepository)
    {
        /** @var User $user */
        $user = $this->getUser();
        $uuid = \strtolower($uuid);
        $item = $itemRepository->findOneBy(['uuid' => $uuid, 'user' => $user]);
        if (!$item) {
            throw new NotFoundHttpException(\sprintf('Item uuid %s not found', $uuid));
        }
        $itemRepository->delete($item);

        return new ApiResponse();
    }
}
