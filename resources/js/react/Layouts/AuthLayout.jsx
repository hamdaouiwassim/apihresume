/**
 * Island shim: the real AuthLayout is rendered by Blade (resources/views/components/layouts).
 * Island pages keep their original wrapper markup, so this just renders children.
 */
export default function AuthLayout({ children }) {
  return <>{children}</>;
}
