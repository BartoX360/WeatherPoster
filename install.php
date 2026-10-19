<?php
// Importe la recette comme « WeatherPoster » avec la position de FlightPoster et l'affichage de l'Echo Show
use App\Models\{Plugin, User};
use App\Services\PluginImportService;
use Illuminate\Http\UploadedFile;
Plugin::where('name', 'like', 'WeatherPoster%')->get()->each->delete();
$file = new UploadedFile('/tmp/weatherposter.zip', 'weatherposter.zip', 'application/zip', null, true);
$p = Plugin::find(app(PluginImportService::class)->importFromZip($file, User::find(1))->id);
$fp = Plugin::find(10)->configuration ?? [];
$p->name = 'WeatherPoster';
$p->configuration = ['lat' => $fp['lat'] ?? '', 'lon' => $fp['lon'] ?? '', 'city' => $fp['city'] ?? '', 'style' => 'color', 'language' => 'fr', 'units' => 'metric'];
$p->save();
$p->updateDataPayload();
$d = App\Models\Device::find(2);
$id = App\Services\ImageGenerationService::generateImage($p->fresh()->render('full', true, $d), 2, $p);
copy(storage_path("app/public/images/generated/$id.png"), '/tmp/wp_live.png');
echo "installed id {$p->id}: ", json_encode($p->fresh()->data_payload['current'] ?? null), "\n";
