import { Navigate, Route, Routes } from 'react-router-dom';
import { AppShell } from './components/AppShell';
import { useAuth } from './auth/useAuth';
import { LoginPage } from './pages/LoginPage';
import { RegisterPage } from './pages/RegisterPage';
import { AcceptTermsPage } from './pages/AcceptTermsPage';
import { DashboardPage } from './pages/DashboardPage';
import { SeasonListPage } from './pages/SeasonListPage';
import { NewSeasonPage } from './pages/NewSeasonPage';
import { SeasonDetailPage } from './pages/SeasonDetailPage';
import { DiseaseScanPage } from './pages/DiseaseScanPage';
import { DealerMapPage } from './pages/DealerMapPage';
import { PricesPage } from './pages/PricesPage';

export function App() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center text-gray-500">
        Loading…
      </div>
    );
  }

  if (!user) {
    return (
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    );
  }

  return (
    <Routes>
      {/* Reconsent gate — rendered outside AppShell so its nav/queries
          (which themselves go through the gated API) don't trigger
          a redirect loop. The page redirects to `?next=` on success. */}
      <Route path="/accept-terms" element={<AcceptTermsPage />} />
      <Route
        path="*"
        element={
          <AppShell>
            <Routes>
              <Route path="/" element={<DashboardPage />} />
              <Route path="/seasons" element={<SeasonListPage />} />
              <Route path="/seasons/new" element={<NewSeasonPage />} />
              <Route path="/seasons/:id" element={<SeasonDetailPage />} />
              <Route path="/disease" element={<DiseaseScanPage />} />
              <Route path="/dealers" element={<DealerMapPage />} />
              <Route path="/prices" element={<PricesPage />} />
              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </AppShell>
        }
      />
    </Routes>
  );
}
