<?php

declare(strict_types=1);

namespace Company\Form;

use Company\Model\CompanyJobPackage as CompanyJobPackageModel;
use Company\Model\Job as JobModel;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * @psalm-suppress MissingTemplateParam
 */
class JobsTransfer extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct('jobsTransferForm');

        $this->add(
            [
                'name' => 'jobs',
                'type' => MultiCheckbox::class,
                'options' => [
                    'label' => $this->translator->translate('Jobs'),
                    'value_options' => [],
                ],
            ],
        );

        $this->add(
            [
                'name' => 'packages',
                'type' => Select::class,
                'options' => [
                    'empty_option' => $this->translator->translate('Select a target job package'),
                    'label' => $this->translator->translate('Packages'),
                    'value_options' => [],
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'options' => [
                    'value' => $this->translator->translate('Transfer Jobs'),
                ],
            ],
        );
    }

    /**
     * @param JobModel[]               $jobs
     * @param CompanyJobPackageModel[] $packages
     */
    public function populateValueOptions(
        array $jobs,
        array $packages,
    ): self {
        $jobsOptions = [];
        foreach ($jobs as $job) {
            $jobsOptions[$job->getId()] = $job->getName();
        }

        $this->get('jobs')->setValueOptions($jobsOptions);

        $packagesOptions = [];
        foreach ($packages as $package) {
            $packagesOptions[$package->getId()] = $package->getContractNumber();
        }

        $this->get('packages')->setValueOptions($packagesOptions);

        return $this;
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'jobs' => [
                'required' => true,
            ],
            'packages' => [
                'required' => true,
            ],
        ];
    }
}
