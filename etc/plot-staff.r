#!/bin/env Rscript
library('jsonlite')
library('purrr')
library('RcppColors')

dummy <- function(x) { x }
srcdir <- getSrcDirectory(dummy)[1]
if (is.na(srcdir)) {
    args <- commandArgs()
    selfarg <- args[startsWith(args, "--file=")]
    srcdir <- dirname(sub('^--file=', '', selfarg))
}
setwd(srcdir)

rooms <- read_json('rooms.json', simplifyVector = TRUE)
rooms <- rooms[rooms$write, ]
rooms <- rooms[rooms$read, ]
servers <- unique(data.frame(id=rooms$server_id,base_url=rooms$base_url))
servers$hostname <- sub('^https?://', '', servers$base_url)
servers$pch = rep_len(seq(15, 18), nrow(servers))
servers$col = rep_len(c("orange",2,3,4,5,6,8), nrow(servers))

rooms$staff = map_vec(rooms$moderators, length) + map_vec(rooms$admins, length)

dir.exists("plots") || dir.create("plots")

for (i in 1:nrow(servers)) {
  
server <- servers[i, ]

rooms.current <- rooms[rooms$server_id == server$id, ]
rooms.current <- rooms.current[order(rooms.current$active_users), ]
rooms.current$pch = rep_len(seq(15, 18), nrow(rooms.current))
rooms.current$col = rep_len(c(3,4,5,6,8), nrow(rooms.current))

rooms.space = min(10,nrow(rooms.current))

minwau <- min(rooms.current$active_users)
maxwau <- max(rooms.current$active_users)
maxstaff <- max(rooms.current$staff)
maxstaff.shown <- max(1, maxstaff*1.05^rooms.space * 1.3)

png(
  filename=paste("plots/", server$hostname, ".plot.png", sep=""),
  width=1080, height=1080, pointsize=30
)

if (maxwau-minwau < 10) {
  xlim <- c(minwau*0.95, maxwau*1.05)
  x.smol <- TRUE
} else {
  xlim <- c(minwau, maxwau)
  x.smol <- FALSE
}

#e <- exp(1)

# nice to have
#line <- function(x) 2 + floor(log(x, base=7)^1.1)
line <- function(x) ceiling(pmax(2,x^0.25))

# low bar (higher base)
line2 <- function(x) 1 + pmax(1,round((0.38*log(x))^1.15))

plot(
  function(x) line(pmax(1,x)),
  main=server$hostname,
  sub=paste("black: ", deparse(body(line)), "; red: ", deparse(body(line2)), sep=""),
  xlim=xlim,
  log=ifelse(minwau==0,"","x"),
  ylab="# of visible staff",
  xlab="# of weekly readers",
  ylim=c(0,maxstaff.shown),
  yaxt="n",
  cex.sub=0.8
)

plot(
  function(x) line2(pmax(1,x)),
  xlim=xlim, col="red", add=TRUE, lwd=2
)

wau.ceil.log <- ceiling(max(0, log10(maxwau)))
maxstaff.ceil.log <- ceiling(max(0, log10(maxstaff)))

grid.col <- "#777777"
grid.col.extra <- "#BBBBBB"

if (x.smol) {
  vlines <- seq(floor(0.9*minwau),ceiling(1.1*maxwau),1)
} else {
vlines <- as.vector(outer(c(1,2,3,5), 10 ^ seq(0, wau.ceil.log)))
vlines.extra <- as.vector(outer(c(1.2,1.4,1.6,1.8,2.25,2.5,2.75,3.5,4,4.5,6,7,8,9), 10 ^ seq(0, wau.ceil.log)))
vlines.extra <- vlines.extra[vlines.extra == floor(vlines.extra)]
abline(v=vlines.extra, col=grid.col.extra, lty="dotted")
}
abline(v=vlines, col=grid.col, lty="dotted")

hlab.gap=ceiling(maxstaff.shown/5)
hlab.gap.logwise.floor = 10^floor(log10(hlab.gap))
hlines.extra <- c()
if (hlab.gap.logwise.floor * 5 < hlab.gap * 1.75) {
  hlab.gap <- hlab.gap.logwise.floor * 5
  hlines.extra <- seq(1,maxstaff.shown,hlab.gap/5)
} else {
  hlab.gap <- hlab.gap.logwise.floor
  if(hlab.gap %% 10 == 0) {
    hlines.extra <- seq(hlab.gap/2,maxstaff.shown,hlab.gap)
  }
}
hlab=seq(0, maxstaff * 1.5, hlab.gap)
axis(2, at=hlab)

hlines=seq(0,maxstaff.shown,hlab.gap)
abline(h=seq(0, maxstaff.shown, hlab.gap), col=grid.col, lty="dotted")
abline(h=hlines.extra, col=grid.col.extra, lty="dotted")

# pch = servers[match(rooms.current$server_id, servers$id), 'pch']
points(
  rooms.current$active_users,
  pmin(rooms.current$staff,maxstaff),
  pch=rooms.current$pch,
  #  col=servers[match(rooms.current$server_id, servers$id), 'col'],
  col=rooms.current$col,
  cex=ifelse(rooms.current$pch==18,1.7,1.5)
)

for (i in 0:2) {
  chunk <- rooms.current[((i*10)+1):((i+1)*10), ]
  chunk <- chunk[!is.na(chunk$token), ]
  if (nrow(chunk) == 0) {
    break
  }
  legend(
    x=(c("topleft","top","topright")[i+1]),
    legend = chunk$token,
    pch=chunk$pch,
    col=chunk$col,
    cex=0.99^rooms.space,
    pt.cex = ifelse(chunk$pch==18,1.2,1)
  )
}

dev.off()


}
