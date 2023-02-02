<?php

namespace Company\Form;

use Company\Model\{
    CompanyJobPackage as CompanyJobPackageModel,
    Job as JobModel,
};
use Laminas\Form\Element\{
    MultiCheckbox,
    Select,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

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
            ]
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
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'options' => [
                    'value' => $this->translator->translate('Transfer Jobs'),
                ],
            ]
        );
    }

    /**
     * @psalm-param array<array-key, JobModel> $jobs
     * @psalm-param array<array-key, CompanyJobPackageModel> $packages
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
