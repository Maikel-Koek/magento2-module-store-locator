<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\StoreLocator
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\StoreLocator\Block\View;

use Magento\Framework\Locale\ListsInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\View\Element\Template\Context;
use Smile\StoreLocator\Model\Retailer\ScheduleManagement;

/**
 * Opening Hours display block
 *
 * @category Smile
 * @package  Smile\StoreLocator
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class OpeningHours extends \Smile\StoreLocator\Block\AbstractView
{
    /**
     * Display calendar up to X days.
     */
    const CALENDAR_MAX_DATE = 6;

    /**
     * Default delay (in minutes) before displaying the "Closing soon" message.
     */
    const DEFAULT_WARNING_THRESOLD = 60;

    /**
     * @var \Smile\StoreLocator\Model\Retailer\ScheduleManagement
     */
    private $scheduleManager;

    /**
     * @var \Magento\Framework\Locale\ListsInterface
     */
    private $localeList;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $localeResolver;

    /**
     * @var integer
     */
    private $closingWarningThresold;

    /**
     * @var \Zend_Locale_Format
     */
    private $localeFormat;

    /**
     * OpeningHours constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context      $context                Application Context
     * @param \Magento\Framework\Registry                           $coreRegistry           Application Registry
     * @param \Smile\StoreLocator\Model\Retailer\ScheduleManagement $scheduleManager        Schedule Manager
     * @param \Magento\Framework\Locale\ListsInterface              $localeLists            Locale lists
     * @param \Magento\Framework\Locale\Resolver                    $localeResolver         Locale Resolver
     * @param \Zend_Locale_Format                                   $localeFormat           Locale Format Resolver
     * @param int                                                   $closingWarningThresold Closing Warning thresold
     * @param array                                                 $data                   Data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ScheduleManagement $scheduleManager,
        ListsInterface $localeLists,
        Resolver $localeResolver,
        \Zend_Locale_Format $localeFormat,
        $closingWarningThresold = self::DEFAULT_WARNING_THRESOLD,
        array $data = []
    ) {
        $this->scheduleManager = $scheduleManager;
        $this->localeList = $localeLists;
        $this->localeResolver = $localeResolver;
        $this->closingWarningThresold = $closingWarningThresold;
        $this->localeFormat = $localeFormat;

        parent::__construct($context, $coreRegistry, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getJsLayout()
    {
        $jsLayout = $this->jsLayout;

        $jsLayout['components']['smile-storelocator-opening-hours']['retailerId'] = $this->getRetailer()->getId();
        $jsLayout['components']['smile-storelocator-opening-hours']['calendar'] = $this->getCalendar();
        $jsLayout['components']['smile-storelocator-opening-hours']['openingHours'] = $this->getOpeningHours();
        $jsLayout['components']['smile-storelocator-opening-hours']['specialOpeningHours'] = $this->getRetailer()->getSpecialOpeningHours();
        $jsLayout['components']['smile-storelocator-opening-hours']['locale'] = $this->localeResolver->getLocale();
        $jsLayout['components']['smile-storelocator-opening-hours']['closingWarningThresold'] = $this->closingWarningThresold;
        $jsLayout['components']['smile-storelocator-opening-hours']['dateFormat'] = strtoupper(DateTime::DATE_INTERNAL_FORMAT);
        $jsLayout['components']['smile-storelocator-opening-hours']['timeFormat'] = $this->localeFormat->getTimeFormat($this->localeResolver->getLocale());

        return json_encode($jsLayout);
    }

    /**
     * Get shop calendar : opening hours for the next X days.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return array
     */
    public function getCalendar()
    {
        $calendar = [];
        $date = $this->getMinDate();
        $calendar[$date->format('Y-m-d')] = $this->scheduleManager->getOpeningHours($this->getRetailer(), $date);

        while ($date < $this->getMaxDate()) {
            $date->add(\DateInterval::createFromDateString('+1 day'));
            $calendar[$date->format('Y-m-d')] = $this->scheduleManager->getOpeningHours($this->getRetailer(), $date);
        }

        return $calendar;
    }

    /**
     * Retrieve opening hours
     *
     * @return array
     */
    public function getOpeningHours()
    {
        $openingHours = [];

        $days = $this->localeList->getOptionWeekdays(true, true);

        foreach (array_keys($days) as $day) {
            $openingHours[$day] = [];
        }

        foreach ($this->getRetailer()->getOpeningHours() as $day => $hours) {
            $openingHours[$day] = $hours;
        }

        return $openingHours;
    }

    /**
     * Get min date to calculate calendar
     *
     * @return \DateTime
     */
    private function getMinDate()
    {
        $date = new \DateTime();

        return $date;
    }

    /**
     * Get max date to calculate calendar
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return \DateTime
     */
    private function getMaxDate()
    {
        $date = $this->getMinDate();
        $date->add(\DateInterval::createFromDateString(sprintf('+%s day', self::CALENDAR_MAX_DATE)));

        return $date;
    }
}