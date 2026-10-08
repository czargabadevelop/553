import { Link, usePage } from '@inertiajs/react';
import { IconGrid, IconMenuDots, IconUsers } from './Icons';

const navItems = [
  { key: 'dashboard', label: 'DASHBOARD', href: '/dashboard', icon: IconGrid },
  { key: 'crud', label: 'CRUD USERS', href: '/crud', icon: IconUsers },
];

export default function Sidebar({ sidebarToggle }) {
  const { url } = usePage();
  const currentPath = url.split('?')[0];

  const isActive = (href) => currentPath === href || currentPath.startsWith(`${href}/`);

  return (
    <aside
      className={`sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0 ${
        sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'
      }`}
    >
      {/* SIDEBAR HEADER */}
      <div
        className={`sidebar-header flex items-center gap-2 pb-7 pt-8 ${
          sidebarToggle ? 'justify-center' : 'justify-between'
        }`}
      >
        <Link href="/dashboard">
          <span className={`logo ${sidebarToggle ? 'hidden' : ''}`}>
            <img className="dark:hidden" src="/images/logo/logo.svg" alt="Logo" />
            <img className="hidden dark:block" src="/images/logo/logo-dark.svg" alt="Logo" />
          </span>

          <img
            className={`logo-icon ${sidebarToggle ? 'lg:block' : 'hidden'}`}
            src="/images/logo/logo-icon.svg"
            alt="Logo"
          />
        </Link>
      </div>
      {/* SIDEBAR HEADER */}

      <div className="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
        <nav>
          <div>
            <h3 className="mb-4 text-xs uppercase leading-[20px] text-gray-400">
              <span className={`menu-group-title ${sidebarToggle ? 'lg:hidden' : ''}`}>MENU</span>

              <IconMenuDots
                className={`mx-auto fill-current menu-group-icon ${
                  sidebarToggle ? 'hidden lg:block' : 'hidden'
                }`}
              />
            </h3>

            <ul className="mb-6 flex flex-col gap-4">
              {navItems.map((item) => {
                const active = isActive(item.href);
                const Icon = item.icon;

                return (
                  <li key={item.key}>
                    <Link
                      href={item.href}
                      className={`menu-item group ${active ? 'menu-item-active' : 'menu-item-inactive'}`}
                    >
                      <Icon className={active ? 'menu-item-icon-active' : 'menu-item-icon-inactive'} />

                      <span className={`menu-item-text ${sidebarToggle ? 'lg:hidden' : ''}`}>
                        {item.label}
                      </span>
                    </Link>
                  </li>
                );
              })}
            </ul>
          </div>
        </nav>
      </div>
    </aside>
  );
}
