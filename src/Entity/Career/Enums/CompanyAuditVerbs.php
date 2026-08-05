<?php

declare(strict_types=1);

namespace App\Entity\Career\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * The actions recorded against a company, each rendering as a full timeline sentence. What happened to the profile and
 * the vacancies themselves is not here: the revision chain and its diffs already say that, in more detail than a
 * sentence could.
 */
enum CompanyAuditVerbs: string
{
    case CompanyCreated = 'company_created';
    case RepresentativeInvited = 'representative_invited';
    case InviteResent = 'invite_resent';
    case InviteRevoked = 'invite_revoked';
    case RepresentativeJoined = 'representative_joined';
    case RepresentativeDisabled = 'representative_disabled';
    case RepresentativeEnabled = 'representative_enabled';
    case RepresentativeRemoved = 'representative_removed';
    case PrimaryContactChanged = 'primary_contact_changed';
    case PackageCreated = 'package_created';
    case PackageUpdated = 'package_updated';
    case PackageDeleted = 'package_deleted';
    case BannerProposed = 'banner_proposed';
    case BannerApproved = 'banner_approved';
    case BannerRejected = 'banner_rejected';
    case BannerReplaced = 'banner_replaced';
    case HighlightSelectionChanged = 'highlight_selection_changed';
    case LogoUploaded = 'logo_uploaded';

    public function message(
        string $actor,
        string $detail,
    ): TranslatableMessage {
        $parameters = [
            '%actor%' => $actor,
            '%detail%' => $detail,
        ];

        return match ($this) {
            self::CompanyCreated => new TranslatableMessage(
                '%actor% added this company',
                $parameters,
            ),
            self::RepresentativeInvited => new TranslatableMessage(
                '%actor% invited %detail% to represent this company',
                $parameters,
            ),
            self::InviteResent => new TranslatableMessage(
                '%actor% sent the invitation for %detail% again',
                $parameters,
            ),
            self::InviteRevoked => new TranslatableMessage(
                '%actor% withdrew the invitation for %detail%',
                $parameters,
            ),
            self::RepresentativeJoined => new TranslatableMessage(
                '%detail% accepted their invitation',
                $parameters,
            ),
            self::RepresentativeDisabled => new TranslatableMessage(
                '%actor% shut %detail% out of the portal',
                $parameters,
            ),
            self::RepresentativeEnabled => new TranslatableMessage(
                '%actor% let %detail% back into the portal',
                $parameters,
            ),
            self::RepresentativeRemoved => new TranslatableMessage(
                '%actor% removed the account of %detail%',
                $parameters,
            ),
            self::PrimaryContactChanged => new TranslatableMessage(
                '%actor% made %detail% the primary contact',
                $parameters,
            ),
            self::PackageCreated => new TranslatableMessage(
                '%actor% added the %detail% package',
                $parameters,
            ),
            self::PackageUpdated => new TranslatableMessage(
                '%actor% changed the %detail% package',
                $parameters,
            ),
            self::PackageDeleted => new TranslatableMessage(
                '%actor% removed the %detail% package',
                $parameters,
            ),
            self::BannerProposed => new TranslatableMessage(
                '%actor% proposed a new banner',
                $parameters,
            ),
            self::BannerApproved => new TranslatableMessage(
                '%actor% approved the proposed banner',
                $parameters,
            ),
            self::BannerRejected => new TranslatableMessage(
                '%actor% rejected the proposed banner',
                $parameters,
            ),
            self::BannerReplaced => new TranslatableMessage(
                '%actor% put a new banner on the site',
                $parameters,
            ),
            self::HighlightSelectionChanged => new TranslatableMessage(
                '%actor% changed which vacancies are highlighted',
                $parameters,
            ),
            self::LogoUploaded => new TranslatableMessage(
                '%actor% uploaded a new logo',
                $parameters,
            ),
        };
    }
}
