/**
 * Card Casino wordmark. Replaces the starter template's stock logo images so
 * the product has one consistent identity in the sidebar, header and login.
 */
export default function BrandMark({ compact = false, className = '' }) {
  return (
    <span className={`inline-flex items-center gap-2.5 ${className}`}>
      <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-lg font-bold leading-none text-white">
        ♠
      </span>
      {!compact && (
        <span className="text-lg font-semibold tracking-tight text-gray-800 dark:text-white/90">
          Card<span className="text-brand-500">Casino</span>
        </span>
      )}
    </span>
  );
}
