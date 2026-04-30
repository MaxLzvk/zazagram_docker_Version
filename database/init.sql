-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : db:3306
-- Généré le : jeu. 30 avr. 2026 à 12:03
-- Version du serveur : 8.0.45
-- Version de PHP : 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `zazagram`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `admin_username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `admin_username`, `action`, `target_type`, `target_id`, `details`, `ip`, `created_at`) VALUES
(1, 109, 'Norihy', 'delete_user', 'user', 8, 'Deleted @frek', '172.21.0.1', '2026-04-29 12:08:56'),
(2, 109, 'Norihy', 'ban_user', 'user', 10, 'Banned @noah_johnson96', '172.21.0.1', '2026-04-29 12:08:57'),
(3, 109, 'Norihy', 'block_ip', 'ip', NULL, 'Blocked IP 172.21.0.1', '172.21.0.1', '2026-04-29 12:11:33'),
(4, 109, 'Norihy', 'block_ip', 'ip', NULL, 'Blocked IP 172.21.0.1', '172.21.0.1', '2026-04-29 12:11:38'),
(5, 109, 'Norihy', 'unblock_ip', 'ip', NULL, 'Unblocked IP 172.21.0.1', '172.21.0.1', '2026-04-29 12:11:47'),
(6, 109, 'Norihy', 'unblock_ip', 'ip', NULL, 'Unblocked IP 172.21.0.1', '172.21.0.1', '2026-04-29 12:12:04'),
(7, 110, 'bhag', 'block_ip', 'ip', NULL, 'Blocked IP 172.21.0.1', '172.21.0.1', '2026-04-30 10:56:39'),
(8, 110, 'bhag', 'unblock_ip', 'ip', NULL, 'Unblocked IP 172.21.0.1', '172.21.0.1', '2026-04-30 10:56:48'),
(9, 109, 'Norihy', 'block_ip', 'ip', NULL, 'Blocked IP 172.21.0.1', '172.21.0.1', '2026-04-30 11:02:04'),
(10, 109, 'Norihy', 'block_ip', 'ip', NULL, 'Blocked IP 172.21.0.1', '172.21.0.1', '2026-04-30 11:02:07'),
(11, 109, 'Norihy', 'ban_user', 'user', 4, 'Banned @sophie_eats', '172.21.0.1', '2026-04-30 11:11:32'),
(12, 109, 'Norihy', 'delete_user', 'user', 9, 'Deleted @fefe', '172.21.0.1', '2026-04-30 11:11:53'),
(13, 109, 'Norihy', 'delete_user', 'user', 9, 'Deleted @', '172.21.0.1', '2026-04-30 11:13:20'),
(14, 109, 'Norihy', 'delete_user', 'user', 9, 'Deleted @', '172.21.0.1', '2026-04-30 11:17:34'),
(15, 109, 'Norihy', 'change_role', 'user', 110, 'Changed role of @bhag from admin to user', '172.21.0.1', '2026-04-30 11:25:52');

-- --------------------------------------------------------

--
-- Structure de la table `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `blocked_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(7, 4, 5, 'Sunday mornings done right!', '2026-04-18T10:45:00Z'),
(9, 84, 109, 'Yes', '2026-04-29T11:43:41Z'),
(10, 111, 109, 'yea', '2026-04-29T12:05:50Z'),
(11, 111, 109, 'hello', '2026-04-30T10:54:25Z'),
(12, 40, 109, 'dwdwd', '2026-04-30T10:54:31Z');

-- --------------------------------------------------------

--
-- Structure de la table `friends`
--

CREATE TABLE `friends` (
  `id` int NOT NULL,
  `requester_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `status` enum('pending','accepted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `friends`
--

INSERT INTO `friends` (`id`, `requester_id`, `receiver_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 109, 14, 'pending', '2026-04-30T11:26:32Z', '2026-04-30T11:26:32Z');

-- --------------------------------------------------------

--
-- Structure de la table `likes`
--

CREATE TABLE `likes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `created_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(16, 109, 84, '2026-04-29T11:43:36Z'),
(17, 109, 40, '2026-04-29T11:43:37Z'),
(18, 109, 111, '2026-04-29T12:05:48Z');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `content`, `is_read`, `created_at`) VALUES
(1, 110, 109, 'TES UNE PUTE', 1, '2026-04-30T10:56:59Z'),
(2, 109, 110, 'OUI JE LE SUIS', 0, '2026-04-30T10:57:20Z');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `actor_id` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int DEFAULT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `reference_id`, `reference_type`, `message`, `is_read`, `created_at`) VALUES
(7, 82, 109, 'like', 84, 'post', 'Norihy liked your post', 0, '2026-04-29T11:43:36Z'),
(8, 38, 109, 'like', 40, 'post', 'Norihy liked your post', 0, '2026-04-29T11:43:37Z'),
(9, 82, 109, 'comment', 84, 'post', 'Norihy commented on your post', 0, '2026-04-29T11:43:41Z'),
(10, 38, 109, 'comment', 40, 'post', 'Norihy commented on your post', 0, '2026-04-30T10:54:31Z'),
(11, 109, 110, 'message', 1, 'message', 'bhag sent you a message', 1, '2026-04-30T10:56:59Z'),
(12, 110, 109, 'message', 2, 'message', 'Norihy sent you a message', 0, '2026-04-30T10:57:20Z'),
(13, 14, 109, 'friend_request', 1, 'friend', 'Norihy sent you a friend request', 0, '2026-04-30T11:26:32Z');

-- --------------------------------------------------------

--
-- Structure de la table `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `caption` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filter` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `created_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `caption`, `image`, `filter`, `created_at`, `updated_at`) VALUES
(4, 4, 'Homemade croissants this Sunday morning ðŸ¥ Recipe in bio!', 'post_4.jpg', 'vintage', '2026-04-18T09:15:00Z', '2026-04-18T09:15:00Z'),
(5, 5, 'Just deployed my first open source project ðŸš€ Check it out!', '', 'none', '2026-04-20T15:00:00Z', '2026-04-20T15:00:00Z'),
(7, 6, 'Hello', '', 'none', '2026-04-23T12:47:11Z', '2026-04-23T12:47:11Z'),
(9, 7, 'NIGGERS', 'post_1776953097_7.png', 'fade', '2026-04-23T14:04:57Z', '2026-04-23T14:04:57Z'),
(12, 10, 'Sunday meal prep done! Eating healthy has never looked (or tasted) so good ðŸ¥—ðŸ±', '', 'none', '2026-01-20T20:05:00Z', '2026-01-20T20:05:00Z'),
(13, 11, 'Finally finished reading this masterpiece. 10/10 would recommend to everyone ðŸ“–âœ¨', '', 'none', '2026-01-21T15:44:00Z', '2026-01-21T15:44:00Z'),
(14, 12, 'Caught the most perfect golden hour today ðŸŒ… My camera does not do it justice.', '', 'none', '2026-04-20T15:48:00Z', '2026-04-20T15:48:00Z'),
(15, 13, 'Started learning guitar three months ago. Just played my first full song without stopping! ðŸŽ¸', '', 'none', '2026-04-13T15:48:00Z', '2026-04-13T15:48:00Z'),
(16, 14, 'Morning run complete âœ… 5km in under 25 minutes. Personal best!', '', 'none', '2026-01-15T11:38:00Z', '2026-01-15T11:38:00Z'),
(17, 15, 'Cooked my grandmother\'s secret pasta recipe for the first time ðŸ Tasted exactly like childhood.', '', 'none', '2026-04-20T11:05:00Z', '2026-04-20T11:05:00Z'),
(18, 16, 'New desk setup is finally complete ðŸ’»ðŸ–¥ï¸ Productivity levels: maximum.', '', 'none', '2026-03-20T13:02:00Z', '2026-03-20T13:02:00Z'),
(19, 17, 'Spontaneous road trip with zero plans was absolutely the right call ðŸš—ðŸŽ¶', '', 'none', '2026-01-28T14:24:00Z', '2026-01-28T14:24:00Z'),
(20, 18, 'My dog discovered snow for the first time today ðŸ¶â„ï¸ The reaction was priceless.', '', 'none', '2026-03-08T18:42:00Z', '2026-03-08T18:42:00Z'),
(21, 19, 'Just launched my side project after 6 months of late nights âœ¨ Dream big, work hard.', '', 'none', '2026-01-21T14:15:00Z', '2026-01-21T14:15:00Z'),
(22, 20, 'Explored a hidden cafÃ© in the city today â˜• Best cortado I\'ve ever had.', '', 'none', '2026-04-09T15:53:00Z', '2026-04-09T15:53:00Z'),
(23, 21, 'Finished my first oil painting! Not perfect but I am proud of every brushstroke ðŸŽ¨', '', 'none', '2026-01-27T18:04:00Z', '2026-01-27T18:04:00Z'),
(24, 22, 'Beach cleanup with friends today ðŸŒŠ Small actions, big impact. Join us next week!', '', 'none', '2026-03-08T00:29:00Z', '2026-03-08T00:29:00Z'),
(25, 23, 'New plant joined the family ðŸŒ¿ Currently unnamed. Open to suggestions!', '', 'none', '2026-02-11T17:27:00Z', '2026-02-11T17:27:00Z'),
(26, 24, 'Homemade sourdough bread finally worked out on attempt number seven ðŸžðŸ†', '', 'none', '2026-02-05T22:55:00Z', '2026-02-05T22:55:00Z'),
(27, 25, 'Caught a street performer doing the most amazing magic tricks downtown ðŸª„ Totally made my day.', '', 'none', '2026-02-28T20:24:00Z', '2026-02-28T20:24:00Z'),
(28, 26, 'Just finished a 30-day no-sugar challenge. Feeling better than ever, not gonna lie ðŸ’ª', '', 'none', '2026-04-17T15:43:00Z', '2026-04-17T15:43:00Z'),
(29, 27, 'Evening sky looked like a painting tonight ðŸŒ† Grateful for these little moments.', '', 'none', '2026-03-26T18:27:00Z', '2026-03-26T18:27:00Z'),
(30, 28, 'My city looks completely different from a rooftop. Recommend doing this at least once ðŸ™ï¸', '', 'none', '2026-01-24T15:32:00Z', '2026-01-24T15:32:00Z'),
(31, 29, 'Spent the whole afternoon at the farmer\'s market and I have zero regrets ðŸ¥¬ðŸ…', '', 'none', '2026-03-28T14:48:00Z', '2026-03-28T14:48:00Z'),
(32, 30, 'Finally visited that museum I\'ve been putting off for two years. Absolutely stunning ðŸ–¼ï¸', '', 'none', '2026-01-22T17:07:00Z', '2026-01-22T17:07:00Z'),
(33, 31, 'Rainy day, warm blanket, favourite movie. Life is good sometimes ðŸŽ¬â˜ï¸', '', 'none', '2026-02-02T16:05:00Z', '2026-02-02T16:05:00Z'),
(34, 32, 'Just hit 1000km cycling this year! The legs are tired but the soul is very full ðŸš´', '', 'none', '2026-01-26T13:30:00Z', '2026-01-26T13:30:00Z'),
(35, 33, 'Adopted this little rescue cat last week. She already owns the entire apartment ðŸ±', '', 'none', '2026-04-09T14:25:00Z', '2026-04-09T14:25:00Z'),
(36, 34, 'Handwritten letters feel so much more personal than emails. Wrote five today ðŸ“¬', '', 'none', '2026-04-04T14:21:00Z', '2026-04-04T14:21:00Z'),
(37, 35, 'Tried surfing for the first time! Stood up on the board twice. Victory ðŸ„', '', 'none', '2026-02-19T14:45:00Z', '2026-02-19T14:45:00Z'),
(38, 36, 'Night market vibes are unmatched. Lights, food, music, people ðŸŒ™ðŸ¢', '', 'none', '2026-01-04T19:15:00Z', '2026-01-04T19:15:00Z'),
(39, 37, 'Deep cleaned my whole apartment today. Brain fog? Gone. Motivation? Back. âœ¨', '', 'none', '2026-04-10T13:36:00Z', '2026-04-10T13:36:00Z'),
(40, 38, 'Just saw the most jaw-dropping live concert of my life. Music heals everything ðŸŽ¤ðŸŽ¶', '', 'none', '2026-04-28T20:06:00Z', '2026-04-28T20:06:00Z'),
(41, 39, 'Went stargazing outside the city last night ðŸŒ  Remembered how tiny we actually are.', '', 'none', '2026-04-14T23:03:00Z', '2026-04-14T23:03:00Z'),
(42, 40, 'Homemade ramen from scratch. Six hours of work. Zero leftovers. Worth it ðŸœ', '', 'none', '2026-04-25T17:15:00Z', '2026-04-25T17:15:00Z'),
(43, 41, 'Joined a community garden this spring ðŸŒ± Can\'t wait to grow my own tomatoes!', '', 'none', '2026-04-06T21:29:00Z', '2026-04-06T21:29:00Z'),
(44, 42, 'Sketchbook is almost full for the second time this year. Progress feels good âœï¸', '', 'none', '2026-04-26T12:34:00Z', '2026-04-26T12:34:00Z'),
(45, 43, 'Watched the sunrise from the pier this morning ðŸŒ… Totally worth the 4am alarm.', '', 'none', '2026-02-09T21:13:00Z', '2026-02-09T21:13:00Z'),
(46, 44, 'Learning Japanese one Duolingo lesson at a time ðŸ‡¯ðŸ‡µ Day 87, still going strong!', '', 'none', '2026-02-16T08:59:00Z', '2026-02-16T08:59:00Z'),
(47, 45, 'Finished building my first mechanical keyboard âŒ¨ï¸ The sound is so satisfying.', '', 'none', '2026-04-24T22:18:00Z', '2026-04-24T22:18:00Z'),
(48, 46, 'Volunteered at the animal shelter today. Those puppies are living rent free in my head now ðŸ¾', '', 'none', '2026-01-27T17:37:00Z', '2026-01-27T17:37:00Z'),
(49, 47, 'Outdoor yoga session on the rooftop ðŸ§˜ Nothing like stretching with a city view.', '', 'none', '2026-02-02T09:04:00Z', '2026-02-02T09:04:00Z'),
(50, 48, 'Made homemade ice cream for the first time ðŸ¦ Mango coconut and it was incredible.', '', 'none', '2026-02-13T12:15:00Z', '2026-02-13T12:15:00Z'),
(51, 49, 'Finally tackled that pile of books on my nightstand. Knocked out three in two weeks ï¿½ï¿½', '', 'none', '2026-01-16T18:13:00Z', '2026-01-16T18:13:00Z'),
(52, 50, 'Went thrifting and found an absolute gem of a vintage jacket ðŸ§¥ðŸ”¥', '', 'none', '2026-03-16T13:19:00Z', '2026-03-16T13:19:00Z'),
(53, 51, 'My sourdough starter is officially named Kevin and he is thriving ðŸž', '', 'none', '2026-01-01T23:06:00Z', '2026-01-01T23:06:00Z'),
(54, 52, 'Tried a pottery class for the first time today. The bowl is... abstract ðŸº Loved every second.', '', 'none', '2026-02-17T16:56:00Z', '2026-02-17T16:56:00Z'),
(55, 53, 'Afternoon thunderstorm had the most beautiful lightning display â›ˆï¸ Nature is wild.', '', 'none', '2026-03-12T14:45:00Z', '2026-03-12T14:45:00Z'),
(56, 54, 'Hosted my first dinner party! The tiramisu disappeared in under ten minutes â˜•ðŸ®', '', 'none', '2026-01-23T18:06:00Z', '2026-01-23T18:06:00Z'),
(57, 55, 'Went to a flea market at 7am and found a working vintage record player ðŸŽµ Best Sunday ever.', '', 'none', '2026-01-05T12:38:00Z', '2026-01-05T12:38:00Z'),
(58, 56, 'The marathon training plan begins today. Twelve weeks to go. Here we go! ðŸƒ', '', 'none', '2026-02-22T16:16:00Z', '2026-02-22T16:16:00Z'),
(59, 57, 'Made candles at a workshop this weekend ðŸ•¯ï¸ Now my apartment smells like cedar and vanilla.', '', 'none', '2026-04-28T15:21:00Z', '2026-04-28T15:21:00Z'),
(60, 58, 'Saw three shooting stars in one night ðŸŒ  Officially the luckiest person alive right now.', '', 'none', '2026-03-06T23:27:00Z', '2026-03-06T23:27:00Z'),
(61, 59, 'Baked a birthday cake for my best friend from scratch ðŸŽ‚ The frosting is chaotic but delicious.', '', 'none', '2026-01-25T11:53:00Z', '2026-01-25T11:53:00Z'),
(62, 60, 'Coffee shop productivity hit different today. Four hours, zero distractions â˜•ðŸ’¡', '', 'none', '2026-02-14T12:23:00Z', '2026-02-14T12:23:00Z'),
(63, 61, 'Swam in the ocean at night for the first time ðŸŒŠðŸŒ™ Terrifying and magical simultaneously.', '', 'none', '2026-02-25T14:22:00Z', '2026-02-25T14:22:00Z'),
(64, 62, 'Redesigned my journal layout and now I actually look forward to writing every morning ðŸ““', '', 'none', '2026-02-11T12:56:00Z', '2026-02-11T12:56:00Z'),
(65, 63, 'Completed a 1000-piece puzzle solo over the weekend ï¿½ï¿½ Chaotic but therapeutic.', '', 'none', '2026-02-25T18:51:00Z', '2026-02-25T18:51:00Z'),
(66, 64, 'Found a hiking trail only locals know about. Kept it secret for exactly five minutes ðŸŒ²', '', 'none', '2026-02-28T10:54:00Z', '2026-02-28T10:54:00Z'),
(67, 65, 'My houseplant collection has officially gotten out of control. Seventeen and counting ðŸŒ¿', '', 'none', '2026-02-28T22:52:00Z', '2026-02-28T22:52:00Z'),
(68, 66, 'Attempted croissants from scratch. They look rough but taste like Paris somehow ðŸ¥', '', 'none', '2026-01-22T14:17:00Z', '2026-01-22T14:17:00Z'),
(69, 67, 'Went to bed at 9pm and woke up naturally at 6am. Is this what adulthood feels like? ï¿½ï¿½âœ¨', '', 'none', '2026-03-21T21:21:00Z', '2026-03-21T21:21:00Z'),
(70, 68, 'Shot my first roll of 35mm film ðŸ“· The results are beautifully imperfect.', '', 'none', '2026-03-09T15:38:00Z', '2026-03-09T15:38:00Z'),
(71, 69, 'Made a friend at the dog park today. The dogs were also there I guess ðŸ¶', '', 'none', '2026-03-16T12:12:00Z', '2026-03-16T12:12:00Z'),
(72, 70, 'Finished a 10-day meditation challenge. Still not enlightened but definitely calmer ðŸ§˜', '', 'none', '2026-04-03T14:04:00Z', '2026-04-03T14:04:00Z'),
(73, 71, 'City bike tour revealed seven neighbourhoods I had never visited. My own city is a stranger ðŸš²', '', 'none', '2026-03-24T11:32:00Z', '2026-03-24T11:32:00Z'),
(74, 72, 'Watched every Miyazaki film in one weekend ðŸŽ¥ Spiritually renewed.', '', 'none', '2026-04-12T20:35:00Z', '2026-04-12T20:35:00Z'),
(75, 73, 'Long weekend camping trip with no wifi and no regrets ðŸ•ï¸ðŸ”¥', '', 'none', '2026-04-23T21:57:00Z', '2026-04-23T21:57:00Z'),
(76, 74, 'Learned to make sushi at home this month ï¿½ï¿½ The rolls are improving one disaster at a time.', '', 'none', '2026-03-16T08:13:00Z', '2026-03-16T08:13:00Z'),
(77, 75, 'Wrote a letter to my future self and sealed it for five years ðŸ“¬ Curious what they will think.', '', 'none', '2026-03-18T23:13:00Z', '2026-03-18T23:13:00Z'),
(78, 76, 'First outdoor concert of the year and it was everything I needed ðŸŽ¸â˜€ï¸', '', 'none', '2026-01-12T17:43:00Z', '2026-01-12T17:43:00Z'),
(79, 77, 'Tried an ice bath for recovery after the long run. Survival mode activated ðŸ§Š', '', 'none', '2026-02-05T07:30:00Z', '2026-02-05T07:30:00Z'),
(80, 78, 'Got up early to walk the city before anyone else was awake. Magic hour ðŸŒ†', '', 'none', '2026-04-24T15:24:00Z', '2026-04-24T15:24:00Z'),
(81, 79, 'Started a gratitude journal three weeks ago. Genuinely changing how I see each day ðŸŒ»', '', 'none', '2026-02-08T07:49:00Z', '2026-02-08T07:49:00Z'),
(82, 80, 'Learned to change a tyre today. Adulting achievement unlocked ðŸ”§ðŸš—', '', 'none', '2026-02-26T23:15:00Z', '2026-02-26T23:15:00Z'),
(83, 81, 'Cooked a new recipe from a different country every week this month ðŸŒðŸ½ï¸', '', 'none', '2026-02-28T23:35:00Z', '2026-02-28T23:35:00Z'),
(84, 82, 'Sketched portraits of strangers on the train. Most of them had no idea ðŸ–Šï¸', '', 'none', '2026-04-28T21:47:00Z', '2026-04-28T21:47:00Z'),
(85, 83, 'Rainbow appeared right over my neighbourhood after the storm ðŸŒˆ Pure cinema.', '', 'none', '2026-03-28T16:17:00Z', '2026-03-28T16:17:00Z'),
(86, 84, 'Signed up for a stand-up comedy open mic night. Terrified and excited equally ðŸŽ¤', '', 'none', '2026-02-10T23:18:00Z', '2026-02-10T23:18:00Z'),
(87, 85, 'My balcony herb garden is producing enough basil for the whole block ðŸŒ¿ðŸƒ', '', 'none', '2026-03-14T09:14:00Z', '2026-03-14T09:14:00Z'),
(88, 86, 'Built a bookshelf from scratch over the weekend. Wonky but it holds books ðŸ“šðŸ”¨', '', 'none', '2026-02-25T14:26:00Z', '2026-02-25T14:26:00Z'),
(89, 87, 'Caught the northern lights on a spontaneous trip and officially ran out of words ðŸŒŒ', '', 'none', '2026-04-14T09:24:00Z', '2026-04-14T09:24:00Z'),
(90, 88, 'Finished my first half-marathon! Crossed the line crying, smiling, and barely walking ðŸ…', '', 'none', '2026-04-19T08:48:00Z', '2026-04-19T08:48:00Z'),
(91, 89, 'Went foraging in the forest with a guide. Came home with chanterelles for dinner ðŸ„', '', 'none', '2026-02-16T15:31:00Z', '2026-02-16T15:31:00Z'),
(92, 90, 'Learned three new songs on the piano this week ðŸŽ¹ Progress is the best feeling.', '', 'none', '2026-03-25T19:53:00Z', '2026-03-25T19:53:00Z'),
(93, 91, 'Spent the day volunteering at a community kitchen. Fed 200 people today ðŸ²â¤ï¸', '', 'none', '2026-01-14T09:27:00Z', '2026-01-14T09:27:00Z'),
(94, 92, 'Bought myself flowers for the first time. Turns out I highly recommend it ðŸŒ·', '', 'none', '2026-02-05T16:13:00Z', '2026-02-05T16:13:00Z'),
(95, 93, 'Cycled to work three days in a row. My commute went from stressful to joyful ðŸš´', '', 'none', '2026-03-25T20:16:00Z', '2026-03-25T20:16:00Z'),
(96, 94, 'Found a tiny bookshop tucked behind a courtyard downtown. Heaven is real ðŸ“š', '', 'none', '2026-01-24T08:41:00Z', '2026-01-24T08:41:00Z'),
(97, 95, 'Tried paddleboarding for the first time and only fell in once. That counts as a win ðŸ„', '', 'none', '2026-01-25T07:53:00Z', '2026-01-25T07:53:00Z'),
(98, 96, 'Homemade granola is a game changer and I will never go back to store bought ðŸ¥£', '', 'none', '2026-02-09T13:07:00Z', '2026-02-09T13:07:00Z'),
(99, 97, 'Took a spontaneous flight to a city I had never visited. Zero plans, maximum fun âœˆï¸', '', 'none', '2026-03-25T20:38:00Z', '2026-03-25T20:38:00Z'),
(100, 98, 'My best drawing yet. Three months of daily practice is paying off ðŸŽ¨', '', 'none', '2026-03-07T08:36:00Z', '2026-03-07T08:36:00Z'),
(101, 99, 'Had a long conversation with a stranger at the bus stop. Reminded me people are fascinating ðŸ—£ï¸', '', 'none', '2026-02-05T16:49:00Z', '2026-02-05T16:49:00Z'),
(102, 100, 'Finished my first short story. It is rough, it is weird, and I am weirdly proud of it ðŸ“', '', 'none', '2026-01-28T10:27:00Z', '2026-01-28T10:27:00Z'),
(103, 101, 'Made a fort in the living room with blankets and watched films all evening ðŸ°ðŸŽ¬', '', 'none', '2026-03-04T21:06:00Z', '2026-03-04T21:06:00Z'),
(104, 102, 'Wild mushroom risotto from scratch tonight ï¿½ï¿½ The patience required builds character.', '', 'none', '2026-04-25T11:46:00Z', '2026-04-25T11:46:00Z'),
(105, 103, 'Bought a disposable camera and shot a whole roll in one afternoon ðŸ“· Miss the uncertainty.', '', 'none', '2026-04-17T22:37:00Z', '2026-04-17T22:37:00Z'),
(106, 104, 'Explored an abandoned greenhouse on the edge of town. Hauntingly beautiful ðŸŒ¿ðŸ“¸', '', 'none', '2026-02-28T10:15:00Z', '2026-02-28T10:15:00Z'),
(107, 105, 'Joined a running club this month. It\'s less running, more laughing, and that\'s perfect ðŸƒðŸ˜‚', '', 'none', '2026-04-14T08:11:00Z', '2026-04-14T08:11:00Z'),
(108, 106, 'Sunday morning farmers market, fresh bread, good coffee, no agenda ðŸ¥–â˜• Peak living.', '', 'none', '2026-03-28T16:56:00Z', '2026-03-28T16:56:00Z'),
(109, 107, 'Made my own pasta for the first time! Fresh tagliatelle and the difference is unreal ðŸ', '', 'none', '2026-01-20T13:46:00Z', '2026-01-20T13:46:00Z'),
(110, 108, 'Just got back from the most incredible hike of my life ðŸ”ï¸ The view from the top was absolutely worth every step.', '', 'none', '2026-02-26T00:31:00Z', '2026-02-26T00:31:00Z'),
(111, 109, 'WE CAN FUCK ALL BITCHIES', 'post_1777464341_109.png', 'none', '2026-04-29T12:05:41Z', '2026-04-29T12:05:41Z');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_avatar.png',
  `role` enum('user','admin','superadmin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `bio`, `profile_picture`, `role`, `is_banned`, `created_at`, `updated_at`) VALUES
(4, 'sophie_eats', 'sophie@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sophie', 'Martin', 'Food blogger | Home chef | Paris ðŸ—¼', 'default_avatar.png', 'user', 1, '2026-02-14T12:00:00Z', '2026-04-30T11:11:32Z'),
(5, 'dev_sam', 'sam@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sam', 'Patel', 'Full-stack dev | Open source | Tea > Coffee', 'default_avatar.png', 'user', 1, '2026-03-01T08:00:00Z', '2026-04-29T11:49:48Z'),
(6, 'YouDaBesh', 'norihynbes@gmail.com', '$2y$12$J9ex.g/MKlPrQdYA2MxQX.476RzaWrm4bL8uN/f/qQMJcxMKVsHoK', 'Norihy', 'Da Besh', '', 'default_avatar.png', 'user', 1, '2026-04-23T12:46:43Z', '2026-04-29T11:49:47Z'),
(7, 'THEBOSSZAZA', 'zaza@gmail.com', '$2y$12$MoPTyGvkl6yM5tpgqJjyTeoT2y59EVKGpQTdAo7Ord1bBno4tLwP2', 'Zaza', 'Owner', 'Oui', 'default_avatar.png', 'user', 0, '2026-04-23T13:51:15Z', '2026-04-23T14:04:27Z'),
(10, 'noah_johnson96', 'noah_johnson96@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Noah', 'Johnson', 'Coffee first, everything else second â˜•', 'default_avatar.png', 'user', 1, '2026-01-19T20:02:00Z', '2026-04-29T12:08:57Z'),
(11, 'oliver_williams39', 'oliver_williams39@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Oliver', 'Williams', 'Explorer at heart ðŸŒ', 'default_avatar.png', 'user', 0, '2026-01-18T13:45:00Z', '2026-01-18T13:45:00Z'),
(12, 'elijah_brown38', 'elijah_brown38@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Elijah', 'Brown', 'Music is my therapy ðŸŽµ', 'default_avatar.png', 'user', 0, '2026-04-19T15:51:00Z', '2026-04-19T15:51:00Z'),
(13, 'james_jones99', 'james_jones99@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'James', 'Jones', 'Gym rat & health nut ðŸ’ª', 'default_avatar.png', 'user', 0, '2026-04-11T15:09:00Z', '2026-04-11T15:09:00Z'),
(14, 'aiden_garcia23', 'aiden_garcia23@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Aiden', 'Garcia', 'Artist in progress ðŸŽ¨', 'default_avatar.png', 'user', 0, '2026-01-13T10:22:00Z', '2026-01-13T10:22:00Z'),
(15, 'lucas_miller15', 'lucas_miller15@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Lucas', 'Miller', 'Dog mom/dad ðŸ¶', 'default_avatar.png', 'user', 1, '2026-04-18T10:59:00Z', '2026-04-29T11:52:52Z'),
(16, 'mason_davis90', 'mason_davis90@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Mason', 'Davis', 'Bookworm & night owl ðŸ“š', 'default_avatar.png', 'user', 0, '2026-03-19T13:45:00Z', '2026-03-19T13:45:00Z'),
(17, 'ethan_wilson47', 'ethan_wilson47@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Ethan', 'Wilson', 'Foodie on a journey ðŸœ', 'default_avatar.png', 'user', 0, '2026-01-28T14:55:00Z', '2026-01-28T14:55:00Z'),
(18, 'logan_moore68', 'logan_moore68@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Logan', 'Moore', 'Chasing sunsets ðŸŒ…', 'default_avatar.png', 'user', 0, '2026-03-06T18:22:00Z', '2026-03-06T18:22:00Z'),
(19, 'emma_taylor99', 'emma_taylor99@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Emma', 'Taylor', 'Tech geek by day, gamer by night ðŸŽ®', 'default_avatar.png', 'user', 0, '2026-01-20T12:34:00Z', '2026-01-20T12:34:00Z'),
(20, 'olivia_anderson69', 'olivia_anderson69@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Olivia', 'Anderson', 'Minimalist living ðŸŒ¿', 'default_avatar.png', 'user', 0, '2026-04-09T14:43:00Z', '2026-04-09T14:43:00Z'),
(21, 'ava_thomas39', 'ava_thomas39@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Ava', 'Thomas', 'Hiking trails & mountain views â›°ï¸', 'default_avatar.png', 'user', 0, '2026-01-26T17:25:00Z', '2026-01-26T17:25:00Z'),
(22, 'isabella_jackson82', 'isabella_jackson82@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Isabella', 'Jackson', 'Fitness coach | DMs open ðŸ’¯', 'default_avatar.png', 'user', 0, '2026-03-07T22:25:00Z', '2026-03-07T22:25:00Z'),
(23, 'sophia_white43', 'sophia_white43@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Sophia', 'White', 'Amateur chef ðŸ³', 'default_avatar.png', 'user', 0, '2026-02-08T15:47:00Z', '2026-02-08T15:47:00Z'),
(24, 'mia_harris56', 'mia_harris56@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Mia', 'Harris', 'Design is thinking made visual ðŸ–Œï¸', 'default_avatar.png', 'user', 0, '2026-02-05T22:05:00Z', '2026-02-05T22:05:00Z'),
(25, 'charlotte_martin29', 'charlotte_martin29@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Charlotte', 'Martin', 'Spreading good vibes only ðŸŒˆ', 'default_avatar.png', 'user', 0, '2026-02-26T20:38:00Z', '2026-02-26T20:38:00Z'),
(26, 'amelia_thompson86', 'amelia_thompson86@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Amelia', 'Thompson', 'Cyclist & weekend warrior ðŸš´', 'default_avatar.png', 'user', 0, '2026-04-17T15:35:00Z', '2026-04-17T15:35:00Z'),
(27, 'harper_young97', 'harper_young97@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Harper', 'Young', 'Photography is my language ðŸ“·', 'default_avatar.png', 'user', 0, '2026-03-25T17:07:00Z', '2026-03-25T17:07:00Z'),
(28, 'evelyn_lee68', 'evelyn_lee68@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Evelyn', 'Lee', 'Cat person ðŸ±', 'default_avatar.png', 'user', 0, '2026-01-24T15:32:00Z', '2026-01-24T15:32:00Z'),
(29, 'benjamin_walker90', 'benjamin_walker90@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Benjamin', 'Walker', 'Entrepreneur & dreamer ðŸš€', 'default_avatar.png', 'user', 0, '2026-03-27T13:09:00Z', '2026-03-27T13:09:00Z'),
(30, 'sebastian_allen79', 'sebastian_allen79@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Sebastian', 'Allen', 'Yoga & mindfulness teacher ðŸ§˜', 'default_avatar.png', 'user', 0, '2026-01-20T17:31:00Z', '2026-01-20T17:31:00Z'),
(31, 'henry_king49', 'henry_king49@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Henry', 'King', 'Sustainable living advocate â™»ï¸', 'default_avatar.png', 'user', 0, '2026-02-02T14:56:00Z', '2026-02-02T14:56:00Z'),
(32, 'alexander_wright72', 'alexander_wright72@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Alexander', 'Wright', 'Software engineer âŒ¨ï¸', 'default_avatar.png', 'user', 0, '2026-01-25T11:08:00Z', '2026-01-25T11:08:00Z'),
(33, 'jack_scott43', 'jack_scott43@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Jack', 'Scott', 'Traveler | 40+ countries ðŸ—ºï¸', 'default_avatar.png', 'user', 0, '2026-04-07T13:45:00Z', '2026-04-07T13:45:00Z'),
(34, 'daniel_torres66', 'daniel_torres66@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Daniel', 'Torres', 'Film buff ðŸŽ¬', 'default_avatar.png', 'user', 0, '2026-04-04T14:14:00Z', '2026-04-04T14:14:00Z'),
(35, 'owen_nguyen85', 'owen_nguyen85@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Owen', 'Nguyen', 'Musician & songwriter ðŸŽ¸', 'default_avatar.png', 'user', 0, '2026-02-19T14:00:00Z', '2026-02-19T14:00:00Z'),
(36, 'ryan_hill39', 'ryan_hill39@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Ryan', 'Hill', 'Digital nomad working from everywhere ðŸ’»', 'default_avatar.png', 'user', 0, '2026-01-02T17:04:00Z', '2026-01-02T17:04:00Z'),
(37, 'nathan_flores95', 'nathan_flores95@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Nathan', 'Flores', 'Nature lover ðŸŒ²', 'default_avatar.png', 'user', 0, '2026-04-07T11:46:00Z', '2026-04-07T11:46:00Z'),
(38, 'caleb_green41', 'caleb_green41@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Caleb', 'Green', 'Positive mind, positive life ðŸŒ»', 'default_avatar.png', 'user', 0, '2026-04-26T20:12:00Z', '2026-04-26T20:12:00Z'),
(39, 'abigail_adams55', 'abigail_adams55@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Abigail', 'Adams', 'Living my best life âœ¨', 'default_avatar.png', 'user', 0, '2026-04-14T21:55:00Z', '2026-04-14T21:55:00Z'),
(40, 'emily_nelson17', 'emily_nelson17@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Emily', 'Nelson', 'Coffee first, everything else second â˜•', 'default_avatar.png', 'user', 0, '2026-04-24T17:51:00Z', '2026-04-24T17:51:00Z'),
(41, 'elizabeth_baker34', 'elizabeth_baker34@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Elizabeth', 'Baker', 'Explorer at heart ðŸŒ', 'default_avatar.png', 'user', 0, '2026-04-05T20:11:00Z', '2026-04-05T20:11:00Z'),
(42, 'sofia_hall19', 'sofia_hall19@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Sofia', 'Hall', 'Music is my therapy ðŸŽµ', 'default_avatar.png', 'user', 0, '2026-04-26T10:03:00Z', '2026-04-26T10:03:00Z'),
(43, 'avery_rivera21', 'avery_rivera21@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Avery', 'Rivera', 'Gym rat & health nut ðŸ’ª', 'default_avatar.png', 'user', 0, '2026-02-06T20:31:00Z', '2026-02-06T20:31:00Z'),
(44, 'ella_campbell17', 'ella_campbell17@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Ella', 'Campbell', 'Artist in progress ðŸŽ¨', 'default_avatar.png', 'user', 0, '2026-02-13T07:24:00Z', '2026-02-13T07:24:00Z'),
(45, 'scarlett_mitchell46', 'scarlett_mitchell46@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Scarlett', 'Mitchell', 'Dog mom/dad ðŸ¶', 'default_avatar.png', 'user', 0, '2026-04-23T22:09:00Z', '2026-04-23T22:09:00Z'),
(46, 'grace_carter17', 'grace_carter17@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Grace', 'Carter', 'Bookworm & night owl ðŸ“š', 'default_avatar.png', 'user', 0, '2026-01-24T17:03:00Z', '2026-01-24T17:03:00Z'),
(47, 'chloe_roberts74', 'chloe_roberts74@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Chloe', 'Roberts', 'Foodie on a journey ðŸœ', 'default_avatar.png', 'user', 0, '2026-02-02T09:54:00Z', '2026-02-02T09:54:00Z'),
(48, 'victoria_phillips96', 'victoria_phillips96@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Victoria', 'Phillips', 'Chasing sunsets ðŸŒ…', 'default_avatar.png', 'user', 0, '2026-02-13T10:56:00Z', '2026-02-13T10:56:00Z'),
(49, 'mateo_evans89', 'mateo_evans89@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Mateo', 'Evans', 'Tech geek by day, gamer by night ðŸŽ®', 'default_avatar.png', 'user', 0, '2026-01-14T17:59:00Z', '2026-01-14T17:59:00Z'),
(50, 'jackson_turner40', 'jackson_turner40@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Jackson', 'Turner', 'Minimalist living ðŸŒ¿', 'default_avatar.png', 'user', 0, '2026-03-13T11:42:00Z', '2026-03-13T11:42:00Z'),
(51, 'wyatt_parker50', 'wyatt_parker50@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Wyatt', 'Parker', 'Hiking trails & mountain views â›°ï¸', 'default_avatar.png', 'user', 0, '2026-01-01T21:39:00Z', '2026-01-01T21:39:00Z'),
(52, 'theodore_collins78', 'theodore_collins78@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Theodore', 'Collins', 'Fitness coach | DMs open ðŸ’¯', 'default_avatar.png', 'user', 0, '2026-02-17T15:08:00Z', '2026-02-17T15:08:00Z'),
(53, 'samuel_edwards41', 'samuel_edwards41@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Samuel', 'Edwards', 'Amateur chef ðŸ³', 'default_avatar.png', 'user', 0, '2026-03-10T12:28:00Z', '2026-03-10T12:28:00Z'),
(54, 'david_stewart88', 'david_stewart88@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'David', 'Stewart', 'Design is thinking made visual ðŸ–Œï¸', 'default_avatar.png', 'user', 0, '2026-01-22T16:59:00Z', '2026-01-22T16:59:00Z'),
(55, 'joseph_sanchez43', 'joseph_sanchez43@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Joseph', 'Sanchez', 'Spreading good vibes only ðŸŒˆ', 'default_avatar.png', 'user', 0, '2026-01-04T11:17:00Z', '2026-01-04T11:17:00Z'),
(56, 'carter_morris53', 'carter_morris53@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Carter', 'Morris', 'Cyclist & weekend warrior ðŸš´', 'default_avatar.png', 'user', 0, '2026-02-22T15:32:00Z', '2026-02-22T15:32:00Z'),
(57, 'julian_rogers21', 'julian_rogers21@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Julian', 'Rogers', 'Photography is my language ðŸ“·', 'default_avatar.png', 'user', 0, '2026-04-27T15:02:00Z', '2026-04-27T15:02:00Z'),
(58, 'luke_reed91', 'luke_reed91@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Luke', 'Reed', 'Cat person ðŸ±', 'default_avatar.png', 'user', 0, '2026-03-06T21:35:00Z', '2026-03-06T21:35:00Z'),
(59, 'penelope_cook24', 'penelope_cook24@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Penelope', 'Cook', 'Entrepreneur & dreamer ðŸš€', 'default_avatar.png', 'user', 0, '2026-01-23T11:34:00Z', '2026-01-23T11:34:00Z'),
(60, 'riley_morgan84', 'riley_morgan84@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Riley', 'Morgan', 'Yoga & mindfulness teacher ðŸ§˜', 'default_avatar.png', 'user', 0, '2026-02-14T11:02:00Z', '2026-02-14T11:02:00Z'),
(61, 'zoey_bell55', 'zoey_bell55@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Zoey', 'Bell', 'Sustainable living advocate â™»ï¸', 'default_avatar.png', 'user', 0, '2026-02-22T14:42:00Z', '2026-02-22T14:42:00Z'),
(62, 'nora_murphy89', 'nora_murphy89@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Nora', 'Murphy', 'Software engineer âŒ¨ï¸', 'default_avatar.png', 'user', 0, '2026-02-08T12:51:00Z', '2026-02-08T12:51:00Z'),
(63, 'lily_bailey13', 'lily_bailey13@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Lily', 'Bailey', 'Traveler | 40+ countries ðŸ—ºï¸', 'default_avatar.png', 'user', 0, '2026-02-24T17:50:00Z', '2026-02-24T17:50:00Z'),
(64, 'eleanor_cooper44', 'eleanor_cooper44@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Eleanor', 'Cooper', 'Film buff ðŸŽ¬', 'default_avatar.png', 'user', 0, '2026-02-26T10:24:00Z', '2026-02-26T10:24:00Z'),
(65, 'hannah_richardson38', 'hannah_richardson38@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Hannah', 'Richardson', 'Musician & songwriter ðŸŽ¸', 'default_avatar.png', 'user', 0, '2026-02-27T21:22:00Z', '2026-02-27T21:22:00Z'),
(66, 'lillian_cox38', 'lillian_cox38@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Lillian', 'Cox', 'Digital nomad working from everywhere ðŸ’»', 'default_avatar.png', 'user', 0, '2026-01-22T13:25:00Z', '2026-01-22T13:25:00Z'),
(67, 'addison_howard45', 'addison_howard45@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Addison', 'Howard', 'Nature lover ðŸŒ²', 'default_avatar.png', 'user', 0, '2026-03-21T19:43:00Z', '2026-03-21T19:43:00Z'),
(68, 'aubrey_ward24', 'aubrey_ward24@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Aubrey', 'Ward', 'Positive mind, positive life ðŸŒ»', 'default_avatar.png', 'user', 0, '2026-03-06T15:02:00Z', '2026-03-06T15:02:00Z'),
(69, 'mohammed_tanaka54', 'mohammed_tanaka54@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Mohammed', 'Tanaka', 'Living my best life âœ¨', 'default_avatar.png', 'user', 0, '2026-03-14T10:24:00Z', '2026-03-14T10:24:00Z'),
(70, 'yusuf_suzuki15', 'yusuf_suzuki15@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Yusuf', 'Suzuki', 'Coffee first, everything else second â˜•', 'default_avatar.png', 'user', 0, '2026-04-01T13:23:00Z', '2026-04-01T13:23:00Z'),
(71, 'tariq_watanabe89', 'tariq_watanabe89@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Tariq', 'Watanabe', 'Explorer at heart ðŸŒ', 'default_avatar.png', 'user', 0, '2026-03-22T10:46:00Z', '2026-03-22T10:46:00Z'),
(72, 'bilal_yamamoto95', 'bilal_yamamoto95@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Bilal', 'Yamamoto', 'Music is my therapy ðŸŽµ', 'default_avatar.png', 'user', 0, '2026-04-11T19:44:00Z', '2026-04-11T19:44:00Z'),
(73, 'rayan_kobayashi34', 'rayan_kobayashi34@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Rayan', 'Kobayashi', 'Gym rat & health nut ðŸ’ª', 'default_avatar.png', 'user', 0, '2026-04-22T19:43:00Z', '2026-04-22T19:43:00Z'),
(74, 'ines_ito88', 'ines_ito88@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Ines', 'Ito', 'Artist in progress ðŸŽ¨', 'default_avatar.png', 'user', 0, '2026-03-13T07:19:00Z', '2026-03-13T07:19:00Z'),
(75, 'yasmine_kato84', 'yasmine_kato84@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Yasmine', 'Kato', 'Dog mom/dad ðŸ¶', 'default_avatar.png', 'user', 0, '2026-03-15T21:28:00Z', '2026-03-15T21:28:00Z'),
(76, 'fatima_sato31', 'fatima_sato31@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Fatima', 'Sato', 'Bookworm & night owl ðŸ“š', 'default_avatar.png', 'user', 0, '2026-01-10T17:05:00Z', '2026-01-10T17:05:00Z'),
(77, 'nadia_nakamura38', 'nadia_nakamura38@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Nadia', 'Nakamura', 'Foodie on a journey ðŸœ', 'default_avatar.png', 'user', 0, '2026-02-05T07:02:00Z', '2026-02-05T07:02:00Z'),
(78, 'leila_hayashi68', 'leila_hayashi68@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Leila', 'Hayashi', 'Chasing sunsets ðŸŒ…', 'default_avatar.png', 'user', 0, '2026-04-21T13:45:00Z', '2026-04-21T13:45:00Z'),
(79, 'hiroshi_patel61', 'hiroshi_patel61@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Hiroshi', 'Patel', 'Tech geek by day, gamer by night ðŸŽ®', 'default_avatar.png', 'user', 0, '2026-02-05T07:57:00Z', '2026-02-05T07:57:00Z'),
(80, 'kenji_sharma38', 'kenji_sharma38@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Kenji', 'Sharma', 'Minimalist living ðŸŒ¿', 'default_avatar.png', 'user', 0, '2026-02-26T21:03:00Z', '2026-02-26T21:03:00Z'),
(81, 'akira_singh68', 'akira_singh68@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Akira', 'Singh', 'Hiking trails & mountain views â›°ï¸', 'default_avatar.png', 'user', 0, '2026-02-26T21:42:00Z', '2026-02-26T21:42:00Z'),
(82, 'yuki_kumar66', 'yuki_kumar66@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Yuki', 'Kumar', 'Fitness coach | DMs open ðŸ’¯', 'default_avatar.png', 'user', 0, '2026-04-27T21:57:00Z', '2026-04-27T21:57:00Z'),
(83, 'naomi_mehta67', 'naomi_mehta67@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Naomi', 'Mehta', 'Amateur chef ðŸ³', 'default_avatar.png', 'user', 0, '2026-03-25T14:53:00Z', '2026-03-25T14:53:00Z'),
(84, 'sora_gupta90', 'sora_gupta90@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Sora', 'Gupta', 'Design is thinking made visual ðŸ–Œï¸', 'default_avatar.png', 'user', 0, '2026-02-09T21:04:00Z', '2026-02-09T21:04:00Z'),
(85, 'ren_shah44', 'ren_shah44@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Ren', 'Shah', 'Spreading good vibes only ðŸŒˆ', 'default_avatar.png', 'user', 0, '2026-03-11T09:08:00Z', '2026-03-11T09:08:00Z'),
(86, 'hana_joshi98', 'hana_joshi98@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Hana', 'Joshi', 'Cyclist & weekend warrior ðŸš´', 'default_avatar.png', 'user', 0, '2026-02-23T13:04:00Z', '2026-02-23T13:04:00Z'),
(87, 'mio_nair79', 'mio_nair79@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Mio', 'Nair', 'Photography is my language ðŸ“·', 'default_avatar.png', 'user', 0, '2026-04-14T08:13:00Z', '2026-04-14T08:13:00Z'),
(88, 'kaito_reddy83', 'kaito_reddy83@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Kaito', 'Reddy', 'Cat person ðŸ±', 'default_avatar.png', 'user', 0, '2026-04-16T07:22:00Z', '2026-04-16T07:22:00Z'),
(89, 'carlos_lopez63', 'carlos_lopez63@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Carlos', 'Lopez', 'Entrepreneur & dreamer ðŸš€', 'default_avatar.png', 'user', 0, '2026-02-16T14:17:00Z', '2026-02-16T14:17:00Z'),
(90, 'diego_martinez59', 'diego_martinez59@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Diego', 'Martinez', 'Yoga & mindfulness teacher ðŸ§˜', 'default_avatar.png', 'user', 0, '2026-03-22T19:46:00Z', '2026-03-22T19:46:00Z'),
(91, 'miguel_hernandez26', 'miguel_hernandez26@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Miguel', 'Hernandez', 'Sustainable living advocate â™»ï¸', 'default_avatar.png', 'user', 0, '2026-01-13T07:05:00Z', '2026-01-13T07:05:00Z'),
(92, 'luis_gonzalez69', 'luis_gonzalez69@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Luis', 'Gonzalez', 'Software engineer âŒ¨ï¸', 'default_avatar.png', 'user', 0, '2026-02-02T15:24:00Z', '2026-02-02T15:24:00Z'),
(93, 'andres_perez51', 'andres_perez51@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Andres', 'Perez', 'Traveler | 40+ countries ðŸ—ºï¸', 'default_avatar.png', 'user', 0, '2026-03-25T19:17:00Z', '2026-03-25T19:17:00Z'),
(94, 'valentina_ramirez70', 'valentina_ramirez70@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Valentina', 'Ramirez', 'Film buff ðŸŽ¬', 'default_avatar.png', 'user', 0, '2026-01-24T08:22:00Z', '2026-01-24T08:22:00Z'),
(95, 'camila_sanchez93', 'camila_sanchez93@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Camila', 'Sanchez', 'Musician & songwriter ðŸŽ¸', 'default_avatar.png', 'user', 0, '2026-01-25T07:15:00Z', '2026-01-25T07:15:00Z'),
(96, 'isabella_torres89', 'isabella_torres89@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Isabella', 'Torres', 'Digital nomad working from everywhere ðŸ’»', 'default_avatar.png', 'user', 0, '2026-02-08T11:30:00Z', '2026-02-08T11:30:00Z'),
(97, 'daniela_flores69', 'daniela_flores69@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Daniela', 'Flores', 'Nature lover ðŸŒ²', 'default_avatar.png', 'user', 0, '2026-03-25T18:10:00Z', '2026-03-25T18:10:00Z'),
(98, 'lucia_reyes30', 'lucia_reyes30@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Lucia', 'Reyes', 'Positive mind, positive life ðŸŒ»', 'default_avatar.png', 'user', 0, '2026-03-04T07:59:00Z', '2026-03-04T07:59:00Z'),
(99, 'priya_ahmed60', 'priya_ahmed60@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Priya', 'Ahmed', 'Living my best life âœ¨', 'default_avatar.png', 'user', 0, '2026-02-03T14:06:00Z', '2026-02-03T14:06:00Z'),
(100, 'arjun_hassan97', 'arjun_hassan97@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Arjun', 'Hassan', 'Coffee first, everything else second â˜•', 'default_avatar.png', 'user', 0, '2026-01-26T08:22:00Z', '2026-01-26T08:22:00Z'),
(101, 'rahul_ali18', 'rahul_ali18@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Rahul', 'Ali', 'Explorer at heart ðŸŒ', 'default_avatar.png', 'user', 0, '2026-03-01T20:52:00Z', '2026-03-01T20:52:00Z'),
(102, 'ananya_omar56', 'ananya_omar56@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Ananya', 'Omar', 'Music is my therapy ðŸŽµ', 'default_avatar.png', 'user', 0, '2026-04-23T11:27:00Z', '2026-04-23T11:27:00Z'),
(103, 'kavya_ibrahim88', 'kavya_ibrahim88@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Kavya', 'Ibrahim', 'Gym rat & health nut ðŸ’ª', 'default_avatar.png', 'user', 0, '2026-04-15T20:52:00Z', '2026-04-15T20:52:00Z'),
(104, 'riya_khan51', 'riya_khan51@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Riya', 'Khan', 'Artist in progress ðŸŽ¨', 'default_avatar.png', 'user', 0, '2026-02-27T09:17:00Z', '2026-02-27T09:17:00Z'),
(105, 'vikram_malik82', 'vikram_malik82@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Vikram', 'Malik', 'Dog mom/dad ðŸ¶', 'default_avatar.png', 'user', 0, '2026-04-11T07:31:00Z', '2026-04-11T07:31:00Z'),
(106, 'nikhil_sheikh37', 'nikhil_sheikh37@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Nikhil', 'Sheikh', 'Bookworm & night owl ðŸ“š', 'default_avatar.png', 'user', 0, '2026-03-26T15:21:00Z', '2026-03-26T15:21:00Z'),
(107, 'aditi_chaudhry81', 'aditi_chaudhry81@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Aditi', 'Chaudhry', 'Foodie on a journey ðŸœ', 'default_avatar.png', 'user', 0, '2026-01-17T13:05:00Z', '2026-01-17T13:05:00Z'),
(108, 'divya_siddiqui72', 'divya_siddiqui72@zazagram.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi', 'Divya', 'Siddiqui', 'Chasing sunsets ðŸŒ…', 'default_avatar.png', 'user', 1, '2026-02-23T22:41:00Z', '2026-04-29T11:50:37Z'),
(109, 'Norihy', 'norihyzf@gmail.com', '$2y$10$o0UOdc.baWMqhweMB2Ot..B.yKfQwTMaGM.5AvKRJgY7jpTEZrAbm', 'Norihy', 'Admin', '', 'avatar_109_1777464316.png', 'superadmin', 0, '2026-04-29T11:43:32Z', '2026-04-29T12:05:16Z'),
(110, 'bhag', 'bhag@gmail.com', '$2y$10$M1nyV1IbzNpbdyRo1zMCFumnTJwsYKqTrQ/WowHCYS8U0jtDxw7EG', 'Bhag', 'Mamazota', '', 'default_avatar.png', 'user', 0, '2026-04-30T10:56:00Z', '2026-04-30 11:25:52');

-- --------------------------------------------------------

--
-- Structure de la table `visitor_logs`
--

CREATE TABLE `visitor_logs` (
  `id` int NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `visited_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `visitor_logs`
--

INSERT INTO `visitor_logs` (`id`, `ip`, `user_id`, `username`, `page`, `user_agent`, `visited_at`) VALUES
(1, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 11:52:52'),
(2, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 11:55:50'),
(3, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 11:56:35'),
(4, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 11:58:25'),
(5, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 11:58:39'),
(6, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 11:58:50'),
(7, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:00:45'),
(8, '172.21.0.1', 109, 'Norihy', '/pages/settings.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:01:50'),
(9, '172.21.0.1', 109, 'Norihy', '/pages/messages.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:01:56'),
(10, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:01:57'),
(11, '172.21.0.1', 109, 'Norihy', '/pages/profile.php?username=Norihy', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:05:12'),
(12, '172.21.0.1', 109, 'Norihy', '/pages/create_post.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:05:20'),
(13, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:05:41'),
(14, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:08:38'),
(15, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:08:53'),
(16, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:08:56'),
(17, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:08:58'),
(18, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:10:02'),
(19, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:11:33'),
(20, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:11:38'),
(21, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:11:47'),
(22, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:12:04'),
(23, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:12:14'),
(24, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:13:34'),
(25, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:13:35'),
(26, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:13:43'),
(27, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:13:47'),
(28, '172.21.0.1', 109, 'Norihy', '/pages/create_post.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:13:48'),
(29, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:13:49'),
(30, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:14:10'),
(31, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:14:12'),
(32, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:14:23'),
(33, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:50:39'),
(34, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:51:32'),
(35, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:51:34'),
(36, '172.21.0.1', 109, 'Norihy', '/pages/notifications.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:52:14'),
(37, '172.21.0.1', 109, 'Norihy', '/pages/messages.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:52:15'),
(38, '172.21.0.1', 109, 'Norihy', '/pages/profile.php?username=Norihy', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:52:17'),
(39, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:52:22'),
(40, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:54:38'),
(41, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:55:00'),
(42, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:55:18'),
(43, '172.21.0.1', NULL, NULL, '/pages/register.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:55:26'),
(44, '172.21.0.1', 110, 'bhag', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:56:01'),
(45, '172.21.0.1', 110, 'bhag', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:56:19'),
(46, '172.21.0.1', 110, 'bhag', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:56:20'),
(47, '172.21.0.1', 110, 'bhag', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:56:39'),
(48, '172.21.0.1', 110, 'bhag', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:56:48'),
(49, '172.21.0.1', 110, 'bhag', '/pages/messages.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:56:52'),
(50, '172.21.0.1', 110, 'bhag', '/pages/messages.php?user=109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:56:56'),
(51, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:57:01'),
(52, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:57:07'),
(53, '172.21.0.1', 109, 'Norihy', '/pages/notifications.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:57:09'),
(54, '172.21.0.1', 109, 'Norihy', '/pages/messages.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:57:15'),
(55, '172.21.0.1', 109, 'Norihy', '/pages/messages.php?user=110', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:57:16'),
(56, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:59:09'),
(57, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:59:52'),
(58, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:59:55'),
(59, '172.21.0.1', 109, 'Norihy', '/pages/settings.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 10:59:59'),
(60, '172.21.0.1', 109, 'Norihy', '/pages/settings.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:01:49'),
(61, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:01:50'),
(62, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:02:04'),
(63, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:02:07'),
(64, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:02:27'),
(65, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:02:31'),
(66, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:02:34'),
(67, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:07:22'),
(68, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:09:54'),
(69, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:11:28'),
(70, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:11:32'),
(71, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:11:53'),
(72, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:13:20'),
(73, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:17:34'),
(74, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:17:57'),
(75, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:17:57'),
(76, '172.21.0.1', 109, 'Norihy', '/pages/messages.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:17:58'),
(77, '172.21.0.1', 109, 'Norihy', '/pages/notifications.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:17:59'),
(78, '172.21.0.1', 109, 'Norihy', '/pages/settings.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:18:04'),
(79, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:20:42'),
(80, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:23:43'),
(81, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:25:40'),
(82, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:25:52'),
(83, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:26:29'),
(84, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:26:39'),
(85, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:29:24'),
(86, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:29:26'),
(87, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:29:31'),
(88, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:31:07'),
(89, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:31:13'),
(90, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:33:45'),
(91, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:34:34'),
(92, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:34:35'),
(93, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:34:38'),
(94, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:40:59'),
(95, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:41:03'),
(96, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:46:30'),
(97, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:46:32'),
(98, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:46:37'),
(99, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:46:42'),
(100, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:52:36'),
(101, '172.21.0.1', NULL, NULL, '/pages/login.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:57:04'),
(102, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:57:08'),
(103, '172.21.0.1', 109, 'Norihy', '/pages/feed.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:59:46'),
(104, '172.21.0.1', 109, 'Norihy', '/pages/notifications.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:59:50'),
(105, '172.21.0.1', 109, 'Norihy', '/pages/admin.php', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-30 11:59:53');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Index pour la table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip` (`ip`),
  ADD KEY `ip_2` (`ip`);

--
-- Index pour la table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_friend_pair` (`requester_id`,`receiver_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Index pour la table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_post_like` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `actor_id` (`actor_id`);

--
-- Index pour la table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `visited_at` (`visited_at`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT pour la table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_post_fk` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_receiver_fk` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_requester_fk` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_post_fk` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_receiver_fk` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_actor_fk` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
