<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Activity Report</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 28px 32px;
            font-size: 11px;
        }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #444; text-transform: uppercase; letter-spacing: 0.5px; }
        .muted { color: #888; font-size: 10px; }
        .generated { color: #888; font-size: 10px; margin-bottom: 14px; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }
        .stat-box {
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 10px 12px;
            background: #fafafa;
        }
        .stat-box .label {
            font-size: 8px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
        }
        .stat-box .value {
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        .secondary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }
        .secondary-stat {
            background: #000;
            color: #fff;
            padding: 10px 14px;
            border-radius: 6px;
        }
        .secondary-stat .label {
            font-size: 8px;
            text-transform: uppercase;
            opacity: 0.7;
            letter-spacing: 0.5px;
        }
        .secondary-stat .value {
            font-size: 18px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9.5px;
        }
        th, td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #000;
            color: #fff;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
        }
        td.num { text-align: center; font-variant-numeric: tabular-nums; }
        tr:nth-child(even) td { background: #fafafa; }
        .score-pill {
            background: #000;
            color: #fff;
            padding: 2px 8px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 9px;
        }
        .footer {
            margin-top: 18px;
            border-top: 1px solid #eee;
            padding-top: 8px;
            font-size: 9px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1><i>User Activity Report</i></h1>
    <div class="muted">Per-user engagement breakdown across all platform activities</div>
    <div class="generated">Generated on {{ date('M j, Y H:i') }}</div>

    <h2>Summary</h2>
    <div class="summary-grid">
        @foreach([
            ['Articles Read', $data['totals']['articlesRead'] ?? 0],
            ['Facts Read', $data['totals']['factsRead'] ?? 0],
            ['Videos Watched', $data['totals']['videosWatched'] ?? 0],
            ['Games Completed', $data['totals']['gamesCompleted'] ?? 0],
            ['Personalities Viewed', $data['totals']['personalityview'] ?? 0],
            ['Quizzes Completed', $data['totals']['quizzesCompleted'] ?? 0],
        ] as $stat)
            <div class="stat-box">
                <div class="label">{{ $stat[0] }}</div>
                <div class="value">{{ number_format($stat[1]) }}</div>
            </div>
        @endforeach
    </div>

    <div class="secondary-grid">
        <div class="secondary-stat">
            <div class="label">Active Users (with engagement)</div>
            <div class="value">{{ number_format($data['active_user_count'] ?? 0) }}</div>
        </div>
        <div class="secondary-stat">
            <div class="label">Cosmic Stars Earned</div>
            <div class="value">{{ number_format($data['totals']['cosmicStars'] ?? 0) }}</div>
        </div>
        <div class="secondary-stat">
            <div class="label">Space Academy XP</div>
            <div class="value">{{ number_format($data['totals']['spaceXp'] ?? 0) }}</div>
        </div>
        <div class="secondary-stat">
            <div class="label">Course Enrollments</div>
            <div class="value">{{ number_format($data['totals']['courseEnrollments'] ?? 0) }}</div>
        </div>
    </div>

    <h2>Top 10 Most Engaged Users</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Role</th>
                <th style="text-align:right;">Engagement Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($data['top_users'] ?? []) as $i => $user)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $user['username'] }}</td>
                    <td>{{ $user['role'] }}</td>
                    <td style="text-align:right;"><span class="score-pill">{{ number_format($user['engagement_score']) }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Per-User Activity Breakdown</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th class="num">Articles</th>
                <th class="num">Facts</th>
                <th class="num">Videos</th>
                <th class="num">Games</th>
                <th class="num">Pers.</th>
                <th class="num">Quizzes</th>
                <th class="num">Stars</th>
                <th class="num">XP</th>
                <th class="num">Courses</th>
                <th class="num">Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($data['rows'] ?? []) as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['username'] }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td>{{ $row['role'] }}</td>
                    <td class="num">{{ $row['activities']['articlesRead'] ?? 0 }}</td>
                    <td class="num">{{ $row['activities']['factsRead'] ?? 0 }}</td>
                    <td class="num">{{ $row['activities']['videosWatched'] ?? 0 }}</td>
                    <td class="num">{{ $row['activities']['gamesCompleted'] ?? 0 }}</td>
                    <td class="num">{{ $row['activities']['personalityview'] ?? 0 }}</td>
                    <td class="num">{{ $row['activities']['quizzesCompleted'] ?? 0 }}</td>
                    <td class="num">{{ $row['cosmic']['stars'] ?? 0 }}</td>
                    <td class="num">{{ $row['space']['xp'] ?? 0 }}</td>
                    <td class="num">{{ $row['courses_enrolled'] ?? 0 }}</td>
                    <td class="num"><span class="score-pill">{{ $row['engagement_score'] ?? 0 }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated by Black Orbit Admin Panel &middot; {{ count($data['rows'] ?? []) }} users in this report
    </div>
</body>
</html>
