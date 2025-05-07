<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\MeetingDocument as MeetingDocumentModel;
use InvalidArgumentException;
use Override;

use function sprintf;

/**
 * @template-extends BaseMapper<MeetingDocumentModel>
 */
class MeetingDocument extends BaseMapper
{
    /**
     * Returns the document with the specified ID.
     *
     * @param int $id Document ID
     *
     * @throws InvalidArgumentException If the document does not exist.
     */
    public function findDocumentOrFail(int $id): MeetingDocumentModel
    {
        $document = $this->find($id);

        if (null === $document) {
            throw new InvalidArgumentException(sprintf("A document with the provided ID '%d' does not exist.", $id));
        }

        return $document;
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return MeetingDocumentModel::class;
    }
}
