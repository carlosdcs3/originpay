<?php
namespace App\Support\Connect;

class Capabilities
{
    public const EMAIL_SEND = 'connect.email.send';
    public const EMAIL_TEMPLATES = 'connect.email.templates';
    public const EMAIL_ANALYTICS = 'connect.email.analytics';

    public const WHATSAPP_SEND = 'connect.whatsapp.send';
    public const WHATSAPP_TEMPLATES = 'connect.whatsapp.templates';
    public const WHATSAPP_CONTACTS = 'connect.whatsapp.contacts';
    public const WHATSAPP_ANALYTICS = 'connect.whatsapp.analytics';

    public const CONTACT_READ = 'connect.contacts.read';
    public const CONTACT_WRITE = 'connect.contacts.write';
    public const CONTACT_IMPORT = 'connect.contacts.import';
    public const CONTACT_EXPORT = 'connect.contacts.export';

    public const TEMPLATES_CREATE = 'connect.templates.create';
    public const TEMPLATES_UPDATE = 'connect.templates.update';
    public const TEMPLATES_DELETE = 'connect.templates.delete';
    public const TEMPLATES_USE = 'connect.templates.use';

    public const CAMPAIGNS_CREATE = 'connect.campaigns.create';
    public const CAMPAIGNS_UPDATE = 'connect.campaigns.update';
    public const CAMPAIGNS_DELETE = 'connect.campaigns.delete';

    public const AUTOMATION_RUN = 'connect.automation.run';

    public const ANALYTICS_VIEW = 'connect.analytics.view';

    public const API_ACCESS = 'connect.api.access';

    public const SEGMENT_READ = 'connect.segments.read';
    public const SEGMENT_WRITE = 'connect.segments.write';
    public const SEGMENT_DELETE = 'connect.segments.delete';

    public const TEMPLATE_READ = 'connect.templates.read';
    public const TEMPLATE_WRITE = 'connect.templates.write';
    public const TEMPLATE_PUBLISH = 'connect.templates.publish';
    public const TEMPLATE_DELETE = 'connect.templates.delete';

    public const CAMPAIGN_READ = 'connect.campaigns.read';
    public const CAMPAIGN_WRITE = 'connect.campaigns.write';
    public const CAMPAIGN_EXECUTE = 'connect.campaigns.execute';
    public const CAMPAIGN_CANCEL = 'connect.campaigns.cancel';
}