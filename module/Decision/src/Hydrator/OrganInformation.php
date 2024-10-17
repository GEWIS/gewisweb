<?php

declare(strict_types=1);

namespace Decision\Hydrator;

use Decision\Model\DecisionLocalisedText as DecisionLocalisedTextModel;
use Decision\Model\OrganInformation as OrganInformationModel;
use InvalidArgumentException;
use Laminas\Hydrator\HydratorInterface;

class OrganInformation implements HydratorInterface
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function extract(object $object): array
    {
        if (!$object instanceof OrganInformationModel) {
            throw new InvalidArgumentException('Object is not an instance of Decision\Model\OrganInformation.');
        }

        return [
            'email' => $object->getEmail(),
            'website' => $object->getWebsite(),
            'tagline' => $object->getTagline()->getValueNL(),
            'taglineEn' => $object->getTagline()->getValueEN(),
            'description' => $object->getDescription()->getValueNL(),
            'descriptionEn' => $object->getDescription()->getValueEN(),
        ];
    }

    public function hydrate(
        array $data,
        object $object,
    ): OrganInformationModel {
        if (!$object instanceof OrganInformationModel) {
            throw new InvalidArgumentException('Object is not an instance of Decision\Model\OrganInformation.');
        }

        if (isset($data['email'])) {
            $object->setEmail($data['email']);
        }

        if (isset($data['website'])) {
            $object->setWebsite($data['website']);
        }

        $shortDescription = $object->getTagline() ?? new DecisionLocalisedTextModel();
        $shortDescription->updateValues(
            $data['taglineEn'] ?? null,
            $data['tagline'] ?? null,
        );
        $object->setTagline($shortDescription);

        $description = $object->getDescription() ?? new DecisionLocalisedTextModel();
        $description->updateValues(
            $data['descriptionEn'] ?? null,
            $data['description'] ?? null,
        );
        $object->setDescription($description);

        return $object;
    }
}
