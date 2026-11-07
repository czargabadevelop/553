import { Link, usePage } from '@inertiajs/react';
import BrandMark from './BrandMark';
import { IconGrid, IconMenuDots, IconUsers } from './Icons';

const playerItems = [
  { key: 'dashboard', label: 'DASHBOARD', href: '/dashboard', icon: IconGrid },
  { key: 'lobby', label: 'LOBBY', href: '/lobby', icon: IconUsers },
  { key: 'wallet', label: 'WALLET', href: '/wallet', icon: IconGrid },
  { key: 'history', label: 'HISTORY', href: '/history', icon: IconUsers },
  { key: 'profile', label: 'PROFILE', href: '/profile', icon: IconUsers },
];

const adminItems = [
  { key: 'admin', label: 'ADMIN OVERVIEW', href: '/admin', icon: IconGrid },
  { key: 'admin-players', label: 'PLAYERS', href: '/admin/players', icon: IconUsers },
  { key: 'admin-rooms', label: 'ROOMS', href: '/admin/rooms', icon: IconGrid },
  { key: 'admin-games', label: 'GAMES', href: '/admin/games', icon: IconUsers },
  { key: 'admin-finance', label: 'FINANCE', href: '/admin/finance', icon: IconGrid },
  { key: 'admin-ledger', label: 'LEDGER', href: '/admin/ledger', icon: IconGrid },
  { key: 'admin-settings', label: 'SETTINGS', href: '/admin/settings', icon: IconGrid },
];

export default function Sidebar({ sidebarToggle }) {
  const { url, props } = usePage();
  const currentPath = url.split('?')[0];
  const roles = props.auth?.user?.roles ?? [];
  const isAdministrator = roles.some((role) => role !== 'player');

  const isActive = (href) => (href === '/admin' ? currentPath === '/admin' : currentPath === href || currentPath.startsWith(`${href}/`));

  const renderItems = (items) => (
    <ul className="mb-6 flex flex-col gap-4">
      {items.map((item) => {
        const active = isActive(item.href);
        const Icon = item.icon;

        return (
          <li key={item.key}>
            <Link href={item.href} className={`menu-item group ${active ? 'menu-item-active' : 'menu-item-inactive'}`}>
              <Icon className={active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'} />
              <span className={`menu-item-text ${sidebarToggle ? 'lg:hidden' : ''}`}>{item.label}</span>
            </Link>
          </li>
        );
      })}
    </ul>
  );

  return (
    <aside
      className={`sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0 ${
        sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'
      }`}
    >
      <div className={`sidebar-header flex items-center gap-2 pb-7 pt-8 ${sidebarToggle ? 'justify-center' : 'justify-between'}`}>
        <Link href="/dashboard">
          <BrandMark compact={sidebarToggle} />
        </Link>
      </div>

      <div className="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
        <nav>
          <div>
            <h3 className="mb-4 text-xs uppercase leading-[20px] text-gray-400">
              <span className={`menu-group-title ${sidebarToggle ? 'lg:hidden' : ''}`}>PLAY</span>
              <IconMenuDots className={`mx-auto fill-current menu-group-icon ${sidebarToggle ? 'hidden lg:block' : 'hidden'}`} />
            </h3>

            {renderItems(playerItems)}

            {isAdministrator && (
              <>
                <h3 className="mb-4 mt-2 text-xs uppercase leading-[20px] text-gray-400">
                  <span className={`menu-group-title ${sidebarToggle ? 'lg:hidden' : ''}`}>ADMINISTRATION</span>
                </h3>
                {renderItems(adminItems)}
              </>
            )}
          </div>
        </nav>
      </div>
    </aside>
  );
}
