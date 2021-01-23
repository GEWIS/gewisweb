<?php

namespace Activity\Service;

use Application\Service\AbstractService;
use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivityField as ActivityFieldModel;
use Activity\Model\ActivityOption as ActivityOptionModel;
use Activity\Model\ActivityTranslation as ActivityTranslationModel;
use Activity\Model\ActivityFieldTranslation as FieldTranslationModel;
use Activity\Model\ActivityOptionTranslation as OptionTranslationModel;

class ActivityTranslator extends AbstractService
{
    /**
     * Get the translated activity for the preferred language if it is available,
     * otherwise get the other language
     *
     * @param ActivityModel $activity
     * @param type $preferredLanguage 'nl' or 'en'
     * @return type
     */
    public function getTranslatedActivity(ActivityModel $activity, $preferredLanguage)
    {
        //Get the preferred language if it is available, otherwise get the other language
        $language = ($preferredLanguage === 'nl' && !is_null($activity->getName())) ||
            ($preferredLanguage === 'en' && is_null($activity->getNameEn())) ? 'nl':'en';

        return $this->createActivityTranslation($activity, $language);
    }

    /**
     * Get the translated fieldset for the preferred language if it is avaiable,
     * otherwise get the other language
     *
     * @param ActivityFieldModel $field
     * @param type $preferredLanguage 'nl' or 'en'
     * @return type
     */
    public function getTranslatedField(ActivityFieldModel $field, $preferredLanguage)
    {
        //Get the preferred language if it is available, otherwise get the other language
        $language = ($preferredLanguage === 'nl' && !is_null($field->getName())) ||
            ($preferredLanguage === 'en' && is_null($field->getNameEn())) ? 'nl':'en';

        return $this->createActivityFieldTranslation($field, $language);
    }

    /**
     * Get the translated version of the signedupdata, i.e. this translates the
     * options. Probably too much effort
     * @param ActivityModel $activity
     * @param type $preferredLanguage
     * @return type
     */
    public function getTranslatedSignedUpData(ActivityModel $activity, $preferredLanguage)
    {
        //Get the preferred language if it is available, otherwise get the other language
        $language = ($preferredLanguage === 'nl' && !is_null($activity->getName())) ||
            ($preferredLanguage === 'en' && is_null($activity->getNameEn())) ? 'nl':'en';
        $signupService = $this->getServiceManager()->get('activity_service_signup');
        $translatedSignupData = $signupService->getSignedUpData($activity);
        for ($i=0; $i<count($translatedSignupData); $i++){
            foreach ($activity->getFields() as $field){
                if ($field->getType() === 3){
                    if (count($translatedSignupData[$i]['values']) != 1) {
                        continue;
                    }
                    $value = $this->createActivityOptionTranslation(
                        $translatedSignupData[$i]['values'][$field->getId()],
                        $language
                        )->getValue();
                    $translatedSignupData[$i]['values'][$field->getId()] = $value;
                }
            }
        }
        return $translatedSignupData;
    }

    /**
     * Create an activity translation of the specified language
     *
     * @param ActivityModel $activity
     * @param type $language
     * @return \Activity\Model\ActivityTranslation
     */
    protected function createActivityTranslation(ActivityModel $activity, $language)
    {
        $activityTranslation = new ActivityTranslationModel();

        //Populate the common-language parts
        $activityTranslation->setId($activity->getId());
        $activityTranslation->setBeginTime($activity->getBeginTime());
        $activityTranslation->setEndTime($activity->getEndTime());
        $activityTranslation->setCanSignUp($activity->getCanSignUp());
        $activityTranslation->setIsFood($activity->getIsFood());
        $activityTranslation->setDisplaySubscribedNumber($activity->getDisplaySubscribedNumber());
        $activityTranslation->setIsMyFuture($activity->getIsMyFuture());
        $activityTranslation->setRequireGEFLITST($activity->getRequireGEFLITST());
        $activityTranslation->setOnlyGEWIS($activity->getOnlyGEWIS());
        $activityTranslation->setApprover($activity->getApprover());
        $activityTranslation->setCreator($activity->getCreator());
        $activityTranslation->setStatus($activity->getStatus());
        $activityTranslation->setSignUps($activity->getSignUps());
        $activityTranslation->setSubscriptionDeadline($activity->getSubscriptionDeadline());
        $activityTranslation->setOrgan($activity->getOrgan());


        if ($language === 'en'){
            $activityTranslation->setName($activity->getNameEn());
            $activityTranslation->setLocation($activity->getLocationEn());
            $activityTranslation->setCosts($activity->getCostsEn());
            $activityTranslation->setDescription($activity->getDescriptionEn());
        }
        if ($language === 'nl'){
            $activityTranslation->setName($activity->getName());
            $activityTranslation->setLocation($activity->getLocation());
            $activityTranslation->setCosts($activity->getCosts());
            $activityTranslation->setDescription($activity->getDescription());
        }
        $fieldTranslations = [];
        foreach ($activity->getFields() as $field){
            $fieldTranslations[] = $this->createActivityFieldTranslation($field, $language);
        }
        $activityTranslation->setFields($fieldTranslations);

        return $activityTranslation;
    }

    protected function createActivityFieldTranslation(ActivityFieldModel $field, $language) {

        $fieldTranslation = new FieldTranslationModel();

        //Populate the common-language parts
        $fieldTranslation->setId($field->getId());
        $fieldTranslation->setActivity($field->getActivity());
        $fieldTranslation->setMinimumValue($field->getMinimumValue());
        $fieldTranslation->setMaximumValue($field->getMaximumValue());
        $fieldTranslation->setType($field->getType());

        if ($language === 'en'){
            $fieldTranslation->setName($field->getNameEn());
        }
        if ($language === 'nl'){
            $fieldTranslation->setName($field->getName());
        }
        $optionTranslations = [];
        foreach ($field->getOptions() as $option){
            $optionTranslations[] = $this->createActivityOptionTranslation($option, $language);
        }
        $fieldTranslation->setOptions($optionTranslations);

        return $fieldTranslation;
    }

    /**
     * Create an option translation of the specified language
     * @param ActivityOptionModel $option
     * @param string $language 'nl' or 'en'
     * @return \Activity\Model\ActivityOptionTranslation
     */
    protected function createActivityOptionTranslation(ActivityOptionModel $option, $language) {

        $optionTranslation = new OptionTranslationModel();

        //Populate the common-language parts
        $optionTranslation->setField($option->getField());
        $optionTranslation->setId($option->getId());

        if ($language === 'en'){
            $optionTranslation->setValue($option->getValueEn());
        }
        if ($language === 'nl'){
            $optionTranslation->setValue($option->getValue());
        }

        return $optionTranslation;
    }
}
