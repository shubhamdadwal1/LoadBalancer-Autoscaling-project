<?php
function getMetadata($path)
{
    $tokenUrl = "http://169.254.169.254/latest/api/token";
    $ch = curl_init($tokenUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-aws-ec2-metadata-token-ttl-seconds: 21600"],
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CUSTOMREQUEST => "PUT",
    ]);
    $token = curl_exec($ch);
    curl_close($ch);

    if (!$token) return "Unavailable";

    $url = "http://169.254.169.254/latest/meta-data/" . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-aws-ec2-metadata-token: " . $token],
        CURLOPT_TIMEOUT => 2,
    ]);
    $value = curl_exec($ch);
    curl_close($ch);

    return $value ?: "Unavailable";
}

function cpuLoad()
{
    $read = function () {
        $line = @file('/proc/stat')[0] ?? '';
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) < 5) return null;
        $user = (int)$parts[1];
        $nice = (int)$parts[2];
        $system = (int)$parts[3];
        $idle = (int)$parts[4];
        $iowait = (int)($parts[5] ?? 0);
        $total = $user + $nice + $system + $idle + $iowait +
            (int)($parts[6] ?? 0) + (int)($parts[7] ?? 0) + (int)($parts[8] ?? 0);
        return [$total, $idle + $iowait];
    };
    $a = $read();
    usleep(300000);
    $b = $read();
    if (!$a || !$b || $b[0] == $a[0]) return 0;
    return round((1 - (($b[1] - $a[1]) / ($b[0] - $a[0]))) * 100);
}

$instanceId = getMetadata("instance-id");
$az = getMetadata("placement/availability-zone");
$cpu = cpuLoad();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>AWS Load Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            color: #222
        }

        .header {
            height: 82px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-left: 180px;
            gap: 35px
        }

        .aws {
            font-weight: 700;
            font-size: 34px;
            letter-spacing: -2px;
            color: #232f3e
        }

        .smile {
            display: block;
            width: 38px;
            height: 4px;
            border-bottom: 4px solid #ff9900;
            border-radius: 50%;
            transform: rotate(-4deg)
        }

        .title {
            font-size: 22px;
            color: #555
        }

        .container {
            margin: 105px auto 0;
            max-width: 1200px
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 18px
        }

        .meta th,
        .meta td {
            text-align: left;
            padding: 18px 14px;
            border-bottom: 1px solid #eee
        }

        .meta th {
            background: #f8f8f8;
            font-weight: 600;
            width: 52%
        }

        .meta td {
            font-family: monospace;
            color: #555
        }

        .cpu {
            margin-top: 72px;
            font-size: 28px;
            font-weight: 700
        }

        .cpu span {
            font-weight: 700
        }

        .button {
            display: inline-block;
            margin-top: 48px;
            padding: 14px 28px;
            background: #ff9900;
            color: #111;
            text-decoration: none;
            border-radius: 4px;
            font-size: 18px
        }

        .note {
            margin-top: 20px;
            color: #777
        }
    </style>
</head>

<body>
    <div class="header">
        <div>
            <div class="aws">aws</div><span class="smile"></span>
        </div>
        <div class="title">Load Test</div>
    </div>
    <div class="container">
        <table class="meta">
            <tr>
                <th>Meta-Data</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>InstanceId</td>
                <td><?= htmlspecialchars($instanceId) ?></td>
            </tr>
            <tr>
                <td>Availability Zone</td>
                <td><?= htmlspecialchars($az) ?></td>
            </tr>
        </table>

        <div class="cpu">Current CPU Load: <span><?= htmlspecialchars($cpu) ?>%</span></div>

        <a class="button" href="load.php">Generate CPU Load</a>
        <div class="note">The load test automatically refreshes every 5 seconds.</div>
    </div>
</body>

</html>