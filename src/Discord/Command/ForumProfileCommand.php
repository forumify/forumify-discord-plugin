<?php

declare(strict_types=1);

namespace Forumify\Discord\Discord\Command;

use Forumify\Core\Entity\User;
use Forumify\Core\Repository\UserRepository;
use Forumify\Core\Twig\Extension\CoreRuntime;
use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Forumify\Discord\Api\DTO\DiscordCommandOption;
use Forumify\Discord\Api\DTO\DiscordEmbed;
use Forumify\Discord\Discord\DiscordCommandInterface;
use Forumify\Discord\Service\BadgeImageComposer;
use Forumify\Forum\Repository\CommentRepository;
use Forumify\Forum\Repository\SubscriptionRepository;
use Forumify\Forum\Repository\TopicRepository;
use Forumify\Forum\Service\UserReputationService;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForumProfileCommand implements DiscordCommandInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Packages $packages,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UrlHelper $urlHelper,
        private readonly TopicRepository $topicRepository,
        private readonly CommentRepository $commentRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly UserReputationService $userRepouitationService,
        private readonly CoreRuntime $coreRuntime,
        private readonly BadgeImageComposer $badgeImageComposer,
    ) {
    }

    public function getName(): string
    {
        return 'forum-profile';
    }

    public function getDescription(): string
    {
        return 'Shows a forum member\'s profile.';
    }

    public function getOptions(): array
    {
        return [
            new DiscordCommandOption()
                ->setName('username')
                ->setDescription('Optional username, if left blank it will show your own profile.'),
        ];
    }

    public function run(array $options): DiscordCommandResult
    {
        $result = new DiscordCommandResult();

        $user = $this->getUserFromOptions($options);
        if ($user === null) {
            $result->content = 'User not found.';
            return $result;
        }

        $url = $this->urlGenerator->generate('forumify_forum_profile', [
            'username' => $user->getUsername(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $embed = new DiscordEmbed(
            title: $user->getDisplayName(),
            url: $url,
            description: '@' . $user->getUsername(),
        );

        $this->addStatsFields($embed, $user);

        $avatar = $user->getAvatar();
        if ($avatar) {
            $avatarUrl = $this->packages->getUrl($avatar, 'forumify.avatar');
            $embed->setThumbnail($this->urlHelper->getAbsoluteUrl($avatarUrl));
        }

        $embed->setFooter(sprintf(
            "%s has been a member since %s, they were last seen %s.",
            $user->getDisplayName(),
            $this->coreRuntime->formatDate($user->getCreatedAt()),
            $user->getLastActivity() !== null
                ? $this->coreRuntime->formatDate($user->getLastActivity())
                : 'never',
        ));

        $badges = $user->getBadges()->slice(0, 6);
        if (!empty($badges)) {
            $badgeComposite = $this->badgeImageComposer->compose($badges);
            $badgeUrl = $this->packages->getUrl($badgeComposite, 'forumify.asset');
            $embed->setImage($this->urlHelper->getAbsoluteUrl($badgeUrl));
        }

        $result->embeds[] = $embed;
        return $result;
    }

    private function addStatsFields(DiscordEmbed $embed, User $user): void
    {
        $topics = $this->topicRepository->count(['createdBy' => $user]);
        $comments = $this->commentRepository->count(['createdBy' => $user]);
        $followers = $this->subscriptionRepository->count([
            'type' => 'user_follow',
            'subjectId' => $user->getId(),
        ]);
        $following = $this->subscriptionRepository->count([
            'user' => $user,
            'type' => 'user_follow',
        ]);
        $reputation = $this->userRepouitationService->getReputation($user);

        $embed
            ->addField('', "**$topics** topics", true)
            ->addField('', "**$comments** comments", true)
            ->addField('', '', true)
            ->addField('', "**$followers** followers", true)
            ->addField('', "**$following** following", true)
            ->addField('', "**$reputation** reputation", true)
            ->addField()
        ;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function getUserFromOptions(array $options): ?User
    {
        $username = $options['username'] ?? null;
        if ($username !== null) {
            return $this->userRepository->findOneBy(['username' => $username]);
        }

        // TODO: find user by idp

        return null;
    }
}
