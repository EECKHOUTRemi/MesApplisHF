# Trivy ignore policy.
# Referenced by the scan-trivy job in .github/workflows/ci.yml via `ignore-policy`.
#
# `linux-libc-dev` ships the Linux kernel headers. A container does not run its
# own kernel — it shares the host's — and these headers are compile-time-only
# files, so kernel CVEs reported against this package are not exploitable inside
# the image. New kernel CVEs land in this package almost weekly, so we filter the
# whole package (rather than individual CVE IDs) to keep CI stable. Real,
# exploitable OS-package CVEs (openssl, glibc, …) still fail the build.
#
# FrankenPHP embedded Go binary CVEs (added 2026-08-06)
# -------------------------------------------------------
# The two CVEs below are in Go libraries compiled into `usr/local/bin/frankenphp`
# (dunglas/frankenphp:1-php8.4). They cannot be patched at the application level
# — a fix requires FrankenPHP to release a new binary with updated deps.
# The build already uses `pull: true` (docker/build-push-action), so the next
# FrankenPHP release that bundles the fixed versions will unblock these
# automatically. Remove both entries once the scan passes without them.
#
# GHSA-r277-6w6q-xmqw (CRITICAL) — kin-openapi auth bypass via NoopAuthenticationFunc
#   Fixed in kin-openapi v0.144.0. Exploitable only via Caddy's admin API (port 2019,
#   bound to localhost), which is not exposed externally.
#
# GHSA-hrxh-6v49-42gf (HIGH) — gRPC-Go xDS RBAC / HTTP/2 vulnerabilities
#   Fixed in grpc v1.82.1. FrankenPHP does not use xDS service-mesh features;
#   the xDS RBAC code path is not reachable in this deployment.
package trivy

default ignore = false

ignore {
	input.PkgName == "linux-libc-dev"
}

ignore {
	input.VulnerabilityID == "GHSA-r277-6w6q-xmqw"
}

ignore {
	input.VulnerabilityID == "GHSA-hrxh-6v49-42gf"
}
