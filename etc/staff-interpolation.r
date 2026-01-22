#!/bin/env Rscript
#staff <- function(x) round(pmax(2,log(x,base=4)^1.2))
staff <- function(x) pmax(2,ceiling(x^0.25))
#staff <- function(x) pmax(2,round(x/50))
xlim=c(1,15000)
plot(staff,xlim=xlim,ylim=c(0,10),log="x",axes=FALSE,ylab="")
axis(1, at=as.vector(outer(c(1,2,5), 10^c(0,1,2,3,4))))
axis(2)
plot(function(x) pmax(2,1 + round((0.38*log(x))^1.15)),xlim=xlim,add=TRUE,col="red")
plot(function(x) pmax(2,1 + round(log(x, base=8))),xlim=xlim,add=TRUE,col="blue")
grid()
pts = rbind(
  c(1,2),
  c(2,2),
  c(3,2),
  c(5,2),
  c(10,2),
  c(20,2),
  c(30,2),
  c(40,2),
  c(50,3),
  c(100,3),
  c(200,4),
  c(500,5),
  c(1000,5),
  c(2000,6),
  c(5000,8),
  c(10000,10),
  c(20000,15),
  c(50000,20)
)
points(pts);
if(0) {
par(new=TRUE)
plot(
  function(x) staff(x)/x,
  log="xy",
  xlim=xlim,
  col="red",
  axes=FALSE,
  ylab=""
)
axis(4, col="red")
}
