<?php
use App\Models\{Plugin, Device, DeviceModel, User};
use App\Services\{PluginImportService, ImageGenerationService};
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

Plugin::where('name', 'WeatherPoster (test)')->get()->each->delete();
$file = new UploadedFile('/tmp/weatherposter.zip', 'weatherposter.zip', 'application/zip', null, true);
$p = Plugin::find(app(PluginImportService::class)->importFromZip($file, User::find(1))->id);
// Lyon : ville de démonstration pour les captures
$base = ['lat' => '45.7640', 'lon' => '4.8357', 'city' => 'Lyon'];
$p->update(['name' => 'WeatherPoster (test)', 'configuration' => $base + ['style' => 'eink', 'language' => 'en', 'units' => 'metric']]);
echo "url=", $p->resolveLiquidVariables($p->polling_url), "\n";

$daily = ['time' => ['2026-10-18', '2026-10-19', '2026-10-20', '2026-10-21'], 'weather_code' => [2, 63, 3, 0],
  'temperature_2m_max' => [17.2, 16.8, 14.5, 15.0], 'temperature_2m_min' => [8.4, 12.1, 7.8, 4.8],
  'precipitation_probability_max' => [20, 90, 30, 5], 'sunrise' => array_fill(0, 4, '2026-10-18T07:57'), 'sunset' => array_fill(0, 4, '2026-10-18T18:44')];
$P = fn($code, $temp = 14.2) => ['current' => ['weather_code' => $code, 'temperature_2m' => $temp, 'apparent_temperature' => $temp - 1.4,
  'relative_humidity_2m' => 72, 'wind_speed_10m' => 14.3, 'wind_direction_10m' => 225], 'daily' => $daily];
$cases = [
  // nom, taille, modèle, style, langue, unités, données, heure, minute (UTC)
  ['fr_color_partly', 'full', 55, 'color', 'fr', 'metric', $P(2), 10, 30],
  ['fr_color_clear', 'full', 55, 'color', 'fr', 'metric', $P(0), 10, 30],
  ['en_eink_clear', 'full', 1, 'eink', 'en', 'imperial', $P(1), 10, 30],
  ['fr_eink_dusk_rain', 'full', 1, 'eink', 'fr', 'metric', $P(63), 16, 50],
  ['fr_color_dusk_showers', 'full', 55, 'color', 'fr', 'metric', $P(80), 16, 50],
  ['en_x_night_snow', 'full', 14, 'eink', 'en', 'metric', $P(73, -2), 21, 0],
  ['en_color_storm', 'full', 55, 'color', 'en', 'metric', $P(95), 10, 30],
  ['fr_eink_fog', 'full', 1, 'eink', 'fr', 'metric', $P(45, 6), 8, 0],
  ['fr_color_overcast', 'full', 55, 'color', 'fr', 'metric', $P(3), 10, 30],
  ['en_color_night_clear', 'full', 55, 'color', 'en', 'imperial', $P(0, 9), 21, 0],
  ['en_hh', 'half_horizontal', 1, 'eink', 'en', 'metric', $P(61), 10, 30],
  ['fr_hv', 'half_vertical', 1, 'eink', 'fr', 'metric', $P(2), 10, 30],
  ['en_q', 'quadrant', 1, 'eink', 'en', 'imperial', $P(0), 21, 0],
  ['fr_error', 'full', 1, 'eink', 'fr', 'metric', ['error' => 'x'], 10, 30],
];
@mkdir('/tmp/wpr'); array_map('unlink', glob('/tmp/wpr/*.png'));
foreach ($cases as [$name, $size, $mid, $style, $lang, $units, $payload, $h, $m]) {
  Carbon::setTestNow(Carbon::create(2026, 10, 18, $h, $m, 0, 'UTC'));
  $p->configuration = $base + ['style' => $style, 'language' => $lang, 'units' => $units];
  $p->data_payload = $payload; $p->saveQuietly();
  $model = DeviceModel::find($mid);
  $dev = new Device(['name' => 'test']); $dev->setRelation('deviceModel', $model);
  $html = $p->fresh()->render($size, true, $dev);
  $pal = $mid == 55 ? App\Models\DevicePalette::find(12) : null;
  $id = ImageGenerationService::generateImageFromModel(markup: $html, deviceModel: $model, palette: $pal);
  copy(storage_path("app/public/images/generated/$id.png"), "/tmp/wpr/$name.png");
  echo "$name ok\n";
}
Carbon::setTestNow();
$p->updateDataPayload();
echo 'live payload: ', substr(json_encode($p->fresh()->data_payload['current'] ?? null), 0, 200), "\n";
