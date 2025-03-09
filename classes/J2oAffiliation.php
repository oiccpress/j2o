<?php

class J2oAffiliation extends J2oObject {

    public ?string $affiliationOrganisationName = null,
        $affiliationOrganisationDivision = null, $affiliationOrganisationPostcode = null,
        $affiliationOrganisationCity = null, $affiliationOrganisationState = null,
        $affiliationOrganisationCountry = null;

    public function __construct($node)
    {
        $this->loadAff($node);
    }

    public function string() {
        $parts = [
            $this->affiliationOrganisationDivision,
            $this->affiliationOrganisationName,
            $this->affiliationOrganisationCity,
            $this->affiliationOrganisationState,
            $this->affiliationOrganisationPostcode,
            $this->affiliationOrganisationCountry,
        ];
        $parts = array_filter($parts);
        return implode(', ', $parts);
    }

    public function loadAff($parent) {
        foreach($parent->childNodes as $node) {
            switch($node->nodeName) {
                case '#text':
                    break;
                case 'country':
                    $this->affiliationOrganisationCountry = $node->getAttribute("country");
                    break;
                case 'addr-line':
                    $part = $node->getAttribute('content-type');
                    switch($part) {
                        case 'street':
                        case 'postbox':
                            printdebug('>>> ignore addr-line ' . $node->nodeName );
                            break;
                        case 'state':
                            $this->affiliationOrganisationState = $node->nodeValue;
                            break;
                        case 'city':
                            $this->affiliationOrganisationCity = $node->nodeValue;
                            break;
                        case 'postcode':
                            $this->affiliationOrganisationPostcode = $node->nodeValue;
                            break;
                        default:
                            $this->logWarning('>>> Unknown addr-line content-type: ' . $part);
                    }
                    break;
                case 'label':
                    printdebug('>> Ignoring label');
                    break;
                case 'institution-wrap':
                    foreach($node->childNodes as $child) {
                        switch($child->nodeName) {
                            case '#text':
                                break;
                            case 'label':
                            case 'institution-id':
                                printdebug('>>> Ignoring ' . $child->nodeName);
                                break;
                            case 'institution':
                                
                                $part = $child->getAttribute('content-type');
                                switch($part) {
                                    case 'org-name':
                                        $this->affiliationOrganisationName = $child->nodeValue;
                                        break;

                                    case 'org-division':
                                        $this->affiliationOrganisationDivision = $child->nodeValue;
                                        break;

                                    default:
                                        $this->logWarning('>>> Unknown institution content-type: ' . $part);
                                }

                                break;
                            default:
                                $this->logWarning('>> Unknown aff institution-wrap element: ' . $child->nodeName);
                        }
                    }
                    break;
                default:
                    $this->logWarning('>> Unknown aff element: ' . $node->nodeName);
            }
        }
    }

}