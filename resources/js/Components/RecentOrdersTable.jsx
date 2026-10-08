const orders = [
  { image: '/images/product/product-01.jpg', name: 'Macbook pro 13”', variants: '2 Variants', category: 'Laptop', price: '$2399.00', status: 'Delivered' },
  { image: '/images/product/product-02.jpg', name: 'Apple Watch Ultra', variants: '1 Variants', category: 'Watch', price: '$879.00', status: 'Pending' },
  { image: '/images/product/product-03.jpg', name: 'iPhone 15 Pro Max', variants: '2 Variants', category: 'SmartPhone', price: '$1869.00', status: 'Delivered' },
  { image: '/images/product/product-04.jpg', name: 'iPad Pro 3rd Gen', variants: '2 Variants', category: 'Electronics', price: '$1699.00', status: 'Canceled' },
  { image: '/images/product/product-05.jpg', name: 'Airpods Pro 2nd Gen', variants: '1 Variants', category: 'Accessories', price: '$240.00', status: 'Delivered' },
];

const statusStyles = {
  Delivered:
    'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
  Pending: 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
  Canceled: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
};

function FilterIcon() {
  return (
    <svg
      className="fill-white stroke-current dark:fill-gray-800"
      width="20"
      height="20"
      viewBox="0 0 20 20"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path d="M2.29004 5.90393H17.7067" stroke="" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
      <path d="M17.7075 14.0961H2.29085" stroke="" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
      <path
        d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z"
        fill=""
        stroke=""
        strokeWidth="1.5"
      />
      <path
        d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z"
        fill=""
        stroke=""
        strokeWidth="1.5"
      />
    </svg>
  );
}

export default function RecentOrdersTable() {
  return (
    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
      <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 className="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Orders</h3>
        </div>

        <div className="flex items-center gap-3">
          <button className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
            <FilterIcon />
            Filter
          </button>

          <button className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
            See all
          </button>
        </div>
      </div>

      <div className="w-full overflow-x-auto">
        <table className="min-w-full">
          <thead>
            <tr className="border-y border-gray-100 dark:border-gray-800">
              {['Products', 'Category', 'Price', 'Status'].map((heading) => (
                <th key={heading} className="py-3">
                  <div className="flex items-center">
                    <p className="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{heading}</p>
                  </div>
                </th>
              ))}
            </tr>
          </thead>

          <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
            {orders.map((order) => (
              <tr key={order.name}>
                <td className="py-3">
                  <div className="flex items-center gap-3">
                    <div className="h-[50px] w-[50px] overflow-hidden rounded-md">
                      <img src={order.image} alt="Product" />
                    </div>
                    <div>
                      <p className="text-theme-sm font-medium text-gray-800 dark:text-white/90">{order.name}</p>
                      <span className="text-theme-xs text-gray-500 dark:text-gray-400">{order.variants}</span>
                    </div>
                  </div>
                </td>
                <td className="py-3">
                  <p className="text-theme-sm text-gray-500 dark:text-gray-400">{order.category}</p>
                </td>
                <td className="py-3">
                  <p className="text-theme-sm text-gray-500 dark:text-gray-400">{order.price}</p>
                </td>
                <td className="py-3">
                  <p className={`rounded-full px-2 py-0.5 text-theme-xs font-medium ${statusStyles[order.status]}`}>
                    {order.status}
                  </p>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
