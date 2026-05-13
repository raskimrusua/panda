import { Link, NavLink, useNavigate } from 'react-router-dom';
import { Camera, LayoutDashboard, LineChart, LogOut, MapPin, Sprout } from 'lucide-react';
import { useAuth } from '@/auth/useAuth';
import { Button } from './ui/Button';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

interface NavItem {
  to: string;
  label: string;
  icon: ReactNode;
}

const NAV: NavItem[] = [
  { to: '/', label: 'Dashboard', icon: <LayoutDashboard className="h-4 w-4" /> },
  { to: '/seasons', label: 'Seasons', icon: <Sprout className="h-4 w-4" /> },
  { to: '/disease', label: 'Disease scan', icon: <Camera className="h-4 w-4" /> },
  { to: '/dealers', label: 'Dealers', icon: <MapPin className="h-4 w-4" /> },
  { to: '/prices', label: 'Market prices', icon: <LineChart className="h-4 w-4" /> },
];

export function AppShell({ children }: { children: ReactNode }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  return (
    <div className="flex min-h-screen flex-col md:flex-row">
      <aside className="md:w-64 md:flex-shrink-0 border-b md:border-b-0 md:border-r border-gray-200 bg-white">
        <div className="p-4 border-b border-gray-100">
          <Link to="/" className="text-xl font-semibold text-brand-700">
            Panda
          </Link>
          <p className="mt-1 text-xs text-gray-500">{user?.tenant?.name ?? user?.name}</p>
        </div>
        <nav className="p-2">
          {NAV.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors',
                  isActive
                    ? 'bg-brand-50 text-brand-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-100',
                )
              }
            >
              {item.icon}
              {item.label}
            </NavLink>
          ))}
        </nav>
        <div className="p-2 mt-2 md:mt-0 md:absolute md:bottom-2 md:w-60">
          <Button
            variant="ghost"
            size="sm"
            onClick={handleLogout}
            className="w-full justify-start"
          >
            <LogOut className="h-4 w-4 mr-2" />
            Sign out
          </Button>
        </div>
      </aside>
      <main className="flex-1 p-4 md:p-8 max-w-5xl mx-auto w-full">{children}</main>
    </div>
  );
}
