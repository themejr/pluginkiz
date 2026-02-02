=== KidQuiz Age-Smart ===
Contributors: kidquiz
Tags: kids, quiz, education, micro-quiz, schools, parents, learning, gamification, wp, wordpress
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Kids quizzes that adapt to age—4–6, 7–9, 10–12. Short sessions, big buttons, safe access codes, and parent snapshots.

== Description ==

KidQuiz Age-Smart is a WordPress plugin for creating ultra-short “Micro-Quizzes” for children. Teachers/admins build ONE question bank, then KidQuiz automatically generates the right quiz experience based on the child’s target age group.

= What makes it unique? =
**Age Rules Engine**
Each question can be tagged with:
- Age Min / Age Max
- Difficulty (Easy/Medium/Hard)
- Reading Level (1–3)
- Optional media (image/audio in future versions)

When you create a quiz, you select a Target Age Group:
- 4–6 (Kindergarten)
- 7–9 (Early Primary)
- 10–12 (Upper Primary)

KidQuiz then generates a Micro-Quiz session automatically:
- fewer/more questions
- shorter/longer session
- age-friendly UI
- age-specific rewards (stickers for 4–6, badges for 7–12)

= Safe access for kids (no accounts) =
Teachers generate “Kid Codes” like: KID-4832  
Kids enter using the code (plus optional nickname), without email, passwords, or complicated registration.

= Parent Snapshot (MVP) =
A simple progress summary for the last X days:
- sessions count
- best skills / weakest skills
- ready-made recommendation text

== Features ==

- Question Bank (CPT): manage questions with age range, difficulty, reading level
- Quiz Builder (CPT): select target age group, session length, reward mode
- Kids Mode UI: one question per screen, large buttons, friendly feedback
- Kid Codes: safe login codes for children (no email)
- REST API endpoints for kids sessions (protected by session token)
- Parent Snapshot report (simple MVP)

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` (or install via ZIP).
2. Activate the plugin through the ‘Plugins’ menu in WordPress.
3. Go to **KidQuiz** in the admin menu:
   - Create Questions
   - Create a Quiz
   - Generate Kid Codes
4. Create a page and add the shortcode:
   `[kidquiz_kids_mode]`
   Or directly open the route:
   `/kidquiz/` (or `/kidquiz/{quiz_id}/`)

== Quick Start ==

1) Create Questions:
- Add age range (Age Min/Age Max)
- Set Reading Level and Difficulty
- Assign Skill taxonomy (for snapshot insights)

2) Create a Quiz:
- Choose Target Age Group (4–6 / 7–9 / 10–12)
- Session length defaults:
  - 4–6: 5 questions / 3 minutes
  - 7–9: 8 questions / 6 minutes
  - 10–12: 10–12 questions / 10 minutes
- Pick reward mode (Stickers/Badges)

3) Generate Kid Codes:
- Use the Kid Codes screen in admin
- Share the code with a child/student

4) Kids Mode:
- Add `[kidquiz_kids_mode]` on a page OR use `/kidquiz/`
- The child enters Kid Code and starts

== Shortcodes ==

= Kids Mode =
[kidquiz_kids_mode]
Optional:
[kidquiz_kids_mode quiz_id="123" title="Math Micro-Quiz"]

= Parent Snapshot =
[kidquiz_parent_snapshot kid_code="KID-4832" days="7"]

== Routes ==

- `/kidquiz/` : Kids Mode (default quiz)
- `/kidquiz/{quiz_id}/` : Kids Mode for a specific quiz

You can change the base slug with the filter:
`kqas_kids_route_slug`

== REST API ==

Namespace: `/wp-json/kqas/v1`

- POST `/start-session`
- GET  `/session-plan`
- POST `/attempt`
- POST `/finish`
- GET  `/snapshot`

Note: session endpoints require `session_token` returned from `/start-session`.

== Privacy ==

KidQuiz is designed for child privacy:
- No child email required
- No passwords needed
- Optional nickname can be disabled via settings
- IP and user-agent storage can be controlled via settings (recommended OFF by default)

== Data & Uninstall ==

By default, uninstalling the plugin does NOT remove data.  
If the admin enables “Delete data on uninstall”, the plugin will remove:
- custom tables (kid codes, sessions, attempts)
- plugin options
- CPT content (questions/quizzes) and related taxonomy terms
- plugin transients

== Frequently Asked Questions ==

= Does it require WooCommerce? =
No.

= Can kids sign up with email and passwords? =
No. The MVP is intentionally code-based for safety and simplicity.

= Can I support images/audio questions? =
Planned in v1.1.

= Will it work on multisite? =
Yes. Uninstall cleanup supports multisite and network activation scenarios.

== Screenshots ==

1. Kids Mode login (Kid Code entry)
2. Kids Mode question screen (big buttons)
3. Quiz settings (Target Age Group, Session length, Reward Mode)
4. Kid Codes management
5. Parent Snapshot (MVP)

== Changelog ==

= 1.0.0 =
- Initial release
- Age Rules Engine (MVP)
- Micro-Quiz generation by age group
- Kids Mode UI
- Kid Codes login (no accounts)
- Parent Snapshot (MVP)
- REST API with session token protection

== Upgrade Notice ==

= 1.0.0 =
First release.
