import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Card, CardBody } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Label } from '@/components/ui/Label';
import { dealersApi, type DealerSearchParams } from '@/api/dealers';

// Leaflet's default marker icons reference broken paths under bundlers.
// Pin official CDN URLs so markers render in dev + prod without copying assets.
delete (L.Icon.Default.prototype as unknown as { _getIconUrl?: () => string })._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

// Default centre: Meru (the JAICA pilot county seat).
const DEFAULT_CENTRE: [number, number] = [0.0463, 37.6559];
const DEFAULT_ZOOM = 8;

const KENYAN_COUNTIES = [
  '', 'Meru', 'Kirinyaga', 'Nyeri', 'Embu', "Murang'a",
  'Machakos', 'Kiambu', 'Nakuru', 'Bungoma', 'Kakamega',
  'Nairobi', 'Kisii', 'Trans Nzoia', 'Uasin Gishu',
];

function CentreMap({ centre }: { centre: [number, number] }) {
  const map = useMap();
  useEffect(() => {
    map.setView(centre, 10);
  }, [map, centre]);
  return null;
}

export function DealerMapPage() {
  const [params, setParams] = useState<DealerSearchParams>({});
  const [userLocation, setUserLocation] = useState<[number, number] | null>(null);
  const [geoError, setGeoError] = useState<string | null>(null);

  const dealersQuery = useQuery({
    queryKey: ['dealers', params],
    queryFn: () => dealersApi.search(params),
  });

  const requestGeolocation = () => {
    setGeoError(null);
    if (!('geolocation' in navigator)) {
      setGeoError('Your browser does not support location.');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        setUserLocation([lat, lng]);
        setParams((p) => ({ ...p, lat, lng, radius_km: p.radius_km ?? 100 }));
      },
      () => {
        setGeoError('Could not get your location. You can still browse by county.');
      },
      { timeout: 10_000 },
    );
  };

  const dealers = dealersQuery.data?.data ?? [];
  const centre: [number, number] = userLocation ?? DEFAULT_CENTRE;

  return (
    <div className="space-y-4">
      <header className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Dealers</h1>
          <p className="text-gray-600">
            {dealers.length} dealer{dealers.length === 1 ? '' : 's'}
            {userLocation && params.radius_km
              ? ` within ${params.radius_km} km`
              : ' in catalogue'}
          </p>
        </div>
        <Button variant="secondary" onClick={requestGeolocation}>
          Use my location
        </Button>
      </header>

      <Card>
        <CardBody className="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div>
            <Label htmlFor="county">County</Label>
            <select
              id="county"
              className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
              value={params.county ?? ''}
              onChange={(e) =>
                setParams((p) => ({ ...p, county: e.target.value || undefined }))
              }
            >
              {KENYAN_COUNTIES.map((c) => (
                <option key={c} value={c}>{c || 'Any'}</option>
              ))}
            </select>
          </div>
          <div>
            <Label htmlFor="stocks">Stocks</Label>
            <select
              id="stocks"
              className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
              value={params.stocks ?? ''}
              onChange={(e) =>
                setParams((p) => ({
                  ...p,
                  stocks: (e.target.value || undefined) as DealerSearchParams['stocks'],
                }))
              }
            >
              <option value="">Any</option>
              <option value="seed">Seed</option>
              <option value="fertiliser">Fertiliser</option>
              <option value="chemical">Chemical</option>
              <option value="equipment">Equipment</option>
            </select>
          </div>
          <div>
            <Label htmlFor="radius">Radius (km)</Label>
            <select
              id="radius"
              className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
              value={params.radius_km ?? ''}
              disabled={!userLocation}
              onChange={(e) =>
                setParams((p) => ({
                  ...p,
                  radius_km: e.target.value ? Number(e.target.value) : undefined,
                }))
              }
            >
              <option value="">All</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
              <option value="250">250</option>
            </select>
          </div>
          <div className="flex items-end">
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={!!params.pcpb_only}
                onChange={(e) =>
                  setParams((p) => ({ ...p, pcpb_only: e.target.checked || undefined }))
                }
              />
              PCPB-certified only
            </label>
          </div>
        </CardBody>
      </Card>

      {geoError && <p className="text-sm text-warn-600">{geoError}</p>}

      <div className="h-96 rounded-lg overflow-hidden border border-gray-200">
        <MapContainer
          center={centre}
          zoom={DEFAULT_ZOOM}
          className="h-full w-full"
          scrollWheelZoom
        >
          <TileLayer
            attribution='&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{y}/{x}.png"
          />
          {userLocation && <CentreMap centre={userLocation} />}
          {dealers.map((d) => (
            <Marker key={d.id} position={[d.gps_lat, d.gps_lng]}>
              <Popup>
                <div className="text-sm">
                  <div className="font-medium">{d.name}</div>
                  <div className="text-gray-600">
                    {d.town ?? d.county}
                    {d.distance_km !== null && ` · ${d.distance_km} km`}
                  </div>
                  {d.phone && (
                    <a href={`tel:${d.phone}`} className="text-brand-700 underline mt-1 block">
                      {d.phone}
                    </a>
                  )}
                  <div className="mt-1 text-xs text-gray-500">
                    {d.stocks.join(' · ')}
                    {d.is_pcpb_certified && ' · PCPB'}
                  </div>
                </div>
              </Popup>
            </Marker>
          ))}
        </MapContainer>
      </div>

      <div className="space-y-2">
        {dealers.map((d) => (
          <Card key={d.id}>
            <CardBody className="flex items-center justify-between">
              <div>
                <div className="font-medium">
                  {d.name}
                  {d.is_pcpb_certified && (
                    <span className="ml-2 text-xs bg-brand-100 text-brand-800 px-1.5 py-0.5 rounded">
                      PCPB
                    </span>
                  )}
                </div>
                <div className="text-sm text-gray-600">
                  {d.town ? `${d.town} · ` : ''}{d.county}
                  {d.distance_km !== null && ` · ${d.distance_km} km`}
                  {' · '}{d.stocks.join(', ')}
                </div>
              </div>
              {d.phone && (
                <a href={`tel:${d.phone}`} className="text-sm text-brand-700 font-medium">
                  Call
                </a>
              )}
            </CardBody>
          </Card>
        ))}
      </div>
    </div>
  );
}
