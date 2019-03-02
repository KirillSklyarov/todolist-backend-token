<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1/item")
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
            ]
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


    /**
     * @Route("/create", name="item_create", methods={"POST", "OPTIONS"})
     */
    public function create()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ItemController.php',
        ]);
    }
}
