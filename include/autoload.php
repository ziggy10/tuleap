<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload80db9aa2687466303ed1e990b1616043($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'pullrequestplugin' => '/pullrequestPlugin.class.php',
            'tuleap\\pullrequest\\additionalactionspresenter' => '/PullRequest/AdditionalActionsPresenter.php',
            'tuleap\\pullrequest\\additionalhelptextpresenter' => '/PullRequest/AdditionalHelpTextPresenter.php',
            'tuleap\\pullrequest\\additionalinfopresenter' => '/PullRequest/AdditionalInfoPresenter.php',
            'tuleap\\pullrequest\\comment\\comment' => '/PullRequest/Comment/Comment.php',
            'tuleap\\pullrequest\\comment\\dao' => '/PullRequest/Comment/Dao.php',
            'tuleap\\pullrequest\\comment\\factory' => '/PullRequest/Comment/Factory.php',
            'tuleap\\pullrequest\\comment\\paginatedcomments' => '/PullRequest/Comment/PaginatedComments.php',
            'tuleap\\pullrequest\\dao' => '/PullRequest/Dao.php',
            'tuleap\\pullrequest\\exception\\invalidbuildstatusexception' => '/PullRequest/Exception/InvalidBuildStatusException.php',
            'tuleap\\pullrequest\\exception\\pullrequestalreadyexistsexception' => '/PullRequest/Exception/PullRequestAlreadyExistsException.php',
            'tuleap\\pullrequest\\exception\\pullrequestcannotbeabandoned' => '/PullRequest/Exception/PullRequestCannotBeAbandoned.php',
            'tuleap\\pullrequest\\exception\\pullrequestcannotbecreatedexception' => '/PullRequest/Exception/PullRequestCannotBeCreatedException.php',
            'tuleap\\pullrequest\\exception\\pullrequestcannotbemerged' => '/PullRequest/Exception/PullRequestCannotBeMerged.php',
            'tuleap\\pullrequest\\exception\\pullrequestnotcreatedexception' => '/PullRequest/Exception/PullRequestNotCreatedException.php',
            'tuleap\\pullrequest\\exception\\pullrequestnotfoundexception' => '/PullRequest/Exception/PullRequestNotFoundException.php',
            'tuleap\\pullrequest\\exception\\pullrequestrepositorymigratedongerritexception' => '/PullRequest/Exception/PullRequestRepositoryMigratedOnGerritException.php',
            'tuleap\\pullrequest\\exception\\unknownbranchnameexception' => '/PullRequest/Exception/UnknownBranchNameException.php',
            'tuleap\\pullrequest\\exception\\unknownreferenceexception' => '/PullRequest/Exception/UnknownReferenceException.php',
            'tuleap\\pullrequest\\factory' => '/PullRequest/Factory.php',
            'tuleap\\pullrequest\\filenulldiff' => '/PullRequest/FileNullDiff.php',
            'tuleap\\pullrequest\\fileunidiff' => '/PullRequest/FileUniDiff.php',
            'tuleap\\pullrequest\\fileunidiffbuilder' => '/PullRequest/FileUniDiffBuilder.php',
            'tuleap\\pullrequest\\gitexec' => '/PullRequest/GitExec.php',
            'tuleap\\pullrequest\\inlinecomment\\dao' => '/PullRequest/InlineComment/Dao.php',
            'tuleap\\pullrequest\\inlinecomment\\inlinecomment' => '/PullRequest/InlineComment/InlineComment.php',
            'tuleap\\pullrequest\\inlinecomment\\inlinecommentupdater' => '/PullRequest/InlineComment/InlineCommentUpdater.php',
            'tuleap\\pullrequest\\plugindescriptor' => '/PullRequestPluginDescriptor.class.php',
            'tuleap\\pullrequest\\plugininfo' => '/PullRequestPluginInfo.class.php',
            'tuleap\\pullrequest\\pullrequest' => '/PullRequest/PullRequest.php',
            'tuleap\\pullrequest\\pullrequestcloser' => '/PullRequest/PullRequestCloser.php',
            'tuleap\\pullrequest\\pullrequestcreator' => '/PullRequest/PullRequestCreator.php',
            'tuleap\\pullrequest\\pullrequestmerger' => '/PullRequest/PullRequestMerger.php',
            'tuleap\\pullrequest\\pullrequestpresenter' => '/PullRequest/PullRequestPresenter.php',
            'tuleap\\pullrequest\\pullrequestupdater' => '/PullRequest/PullRequestUpdater.php',
            'tuleap\\pullrequest\\reference\\projectreferenceretriever' => '/PullRequest/Reference/ProjectReferenceRetriever.php',
            'tuleap\\pullrequest\\reference\\reference' => '/PullRequest/Reference/Reference.php',
            'tuleap\\pullrequest\\reference\\referencedao' => '/PullRequest/Reference/ReferenceDao.php',
            'tuleap\\pullrequest\\reference\\referencefactory' => '/PullRequest/Reference/ReferenceFactory.php',
            'tuleap\\pullrequest\\rest\\resourcesinjector' => '/PullRequest/REST/ResourcesInjector.class.php',
            'tuleap\\pullrequest\\rest\\v1\\commentpostrepresentation' => '/PullRequest/REST/v1/CommentPOSTRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\commentrepresentation' => '/PullRequest/REST/v1/CommentRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\gitrepositoryreference' => '/PullRequest/REST/v1/GitRepositoryReference.php',
            'tuleap\\pullrequest\\rest\\v1\\mimedetector' => '/PullRequest/REST/v1/MimeDetector.php',
            'tuleap\\pullrequest\\rest\\v1\\paginatedcommentsrepresentations' => '/PullRequest/REST/v1/PaginatedCommentsRepresentations.php',
            'tuleap\\pullrequest\\rest\\v1\\paginatedcommentsrepresentationsbuilder' => '/PullRequest/REST/v1/PaginatedCommentsRepresentationsBuilder.php',
            'tuleap\\pullrequest\\rest\\v1\\paginatedtimelinerepresentation' => '/PullRequest/REST/v1/PaginatedTimelineRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\paginatedtimelinerepresentationbuilder' => '/PullRequest/REST/v1/PaginatedTimelineRepresentationBuilder.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestfilerepresentation' => '/PullRequest/REST/v1/PullRequestFileRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestfilerepresentationfactory' => '/PullRequest/REST/v1/PullRequestFileRepresentationFactory.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestfileunidiffrepresentation' => '/PullRequest/REST/v1/PullRequestFileUniDiffRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestinlinecommentpostrepresentation' => '/PullRequest/REST/v1/PullRequestInlineCommentPOSTRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestinlinecommentrepresentation' => '/PullRequest/REST/v1/PullRequestInlineCommentRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestinlinecommentrepresentationbuilder' => '/PullRequest/REST/v1/PullRequestInlineCommentRepresentationBuilder.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestlineunidiffrepresentation' => '/PullRequest/REST/v1/PullRequestLineUniDiffRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestpatchrepresentation' => '/PullRequest/REST/v1/PullRequestPATCHRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestpostrepresentation' => '/PullRequest/REST/v1/PullRequestPOSTRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestreference' => '/PullRequest/REST/v1/PullRequestReference.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestrepresentation' => '/PullRequest/REST/v1/PullRequestRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestrepresentationfactory' => '/PullRequest/REST/v1/PullRequestRepresentationFactory.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestshortstatrepresentation' => '/PullRequest/REST/v1/PullRequestShortStatRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\pullrequestsresource' => '/PullRequest/REST/v1/PullRequestsResource.php',
            'tuleap\\pullrequest\\rest\\v1\\repositorypullrequestrepresentation' => '/PullRequest/REST/v1/RepositoryPullRequestRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\repositoryresource' => '/PullRequest/REST/v1/RepositoryResource.php',
            'tuleap\\pullrequest\\rest\\v1\\timelineeventrepresentation' => '/PullRequest/REST/v1/TimelineEventRepresentation.php',
            'tuleap\\pullrequest\\rest\\v1\\timelineinlinecommentrepresentation' => '/PullRequest/REST/v1/TimelineInlineCommentRepresentation.php',
            'tuleap\\pullrequest\\router' => '/PullRequest/Router.php',
            'tuleap\\pullrequest\\shortstat' => '/PullRequest/ShortStat.php',
            'tuleap\\pullrequest\\timeline\\dao' => '/PullRequest/Timeline/Dao.php',
            'tuleap\\pullrequest\\timeline\\factory' => '/PullRequest/Timeline/Factory.php',
            'tuleap\\pullrequest\\timeline\\paginatedtimeline' => '/PullRequest/Timeline/PaginatedTimeline.php',
            'tuleap\\pullrequest\\timeline\\timelineevent' => '/PullRequest/Timeline/TimelineEvent.php',
            'tuleap\\pullrequest\\timeline\\timelineeventcreator' => '/PullRequest/Timeline/TimelineEventCreator.php',
            'tuleap\\pullrequest\\tooltip\\presenter' => '/PullRequest/Tooltip/Presenter.php',
            'tuleap\\pullrequest\\unidiffline' => '/PullRequest/UniDiffLine.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload80db9aa2687466303ed1e990b1616043');
// @codeCoverageIgnoreEnd
